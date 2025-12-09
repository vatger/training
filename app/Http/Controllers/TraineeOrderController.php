<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TraineeOrderController extends Controller
{
    public function updateOrder(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'trainee_ids' => 'required|array',
            'trainee_ids.*' => 'integer|exists:users,id',
        ]);

        $courseId = $request->course_id;
        $traineeIds = $request->trainee_ids;

        $course = \App\Models\Course::findOrFail($courseId);
        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'You cannot modify this course'], 403);
        }

        try {
            DB::transaction(function () use ($courseId, $traineeIds, $user) {
                foreach ($traineeIds as $index => $traineeId) {
                    DB::table('course_trainees')
                        ->where('course_id', $courseId)
                        ->where('user_id', $traineeId)
                        ->update([
                            'custom_order' => $index + 1,
                            'custom_order_mentor_id' => $user->id,
                            'updated_at' => now(),
                        ]);
                }
            });

            Log::info('Trainee order updated', [
                'mentor_id' => $user->id,
                'course_id' => $courseId,
                'trainee_count' => count($traineeIds),
            ]);

            return $this->returnWithRefreshedCourse($courseId, $user);

        } catch (\Exception $e) {
            Log::error('Error updating trainee order', [
                'mentor_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to update trainee order']);
        }
    }

    public function resetOrder(Request $request)
    {
        $user = $request->user();

        if (!$user->isMentor() && !$user->is_superuser) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $courseId = $request->course_id;

        $course = \App\Models\Course::findOrFail($courseId);
        if (!$user->is_superuser && !$user->is_admin && !$user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'You cannot modify this course'], 403);
        }

        try {
            DB::table('course_trainees')
                ->where('course_id', $courseId)
                ->where('custom_order_mentor_id', $user->id)
                ->update([
                    'custom_order' => null,
                    'custom_order_mentor_id' => null,
                    'updated_at' => now(),
                ]);

            Log::info('Trainee order reset', [
                'mentor_id' => $user->id,
                'course_id' => $courseId,
            ]);

            return $this->returnWithRefreshedCourse($courseId, $user);

        } catch (\Exception $e) {
            Log::error('Error resetting trainee order', [
                'mentor_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to reset trainee order']);
        }
    }

    protected function returnWithRefreshedCourse($courseId, $user)
    {
        if ($user->is_superuser || $user->is_admin) {
            $courses = \App\Models\Course::select(['id', 'name', 'position', 'type', 'solo_station'])
                ->withCount('activeTrainees')
                ->get();
        } else {
            $courses = $user->mentorCourses()
                ->select(['courses.id', 'courses.name', 'courses.position', 'courses.type', 'courses.solo_station'])
                ->withCount('activeTrainees')
                ->get();
        }

        $ctrCourses = $courses->filter(fn($c) => $c->position === 'CTR');
        $nonCtrCourses = $courses->filter(fn($c) => $c->position !== 'CTR');

        $positionOrder = ['GND' => 1, 'TWR' => 2, 'APP' => 3];
        $nonCtrCourses = $nonCtrCourses
            ->sortBy(function ($course) use ($positionOrder) {
                return $positionOrder[$course->position] ?? 999;
            })
            ->sortBy('name');

        $ctrCourses = $ctrCourses->sortBy('name');
        $courses = $nonCtrCourses->concat($ctrCourses)->values();

        $coursesMetadata = $courses->map(function ($course) use ($courseId, $user) {
            if ($course->id === $courseId) {
                try {
                    $fullCourse = \App\Models\Course::find($courseId);
                    if ($fullCourse) {
                        $courseData = app(\App\Http\Controllers\MentorOverviewController::class)->loadCourseData($fullCourse, $user);
                        $courseData['loaded'] = true;
                        return $courseData;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to load course', ['course_id' => $courseId, 'error' => $e->getMessage()]);
                }
            }

            return [
                'id' => $course->id,
                'name' => $course->name,
                'position' => $course->position,
                'type' => $course->type,
                'soloStation' => $course->solo_station,
                'activeTrainees' => $course->active_trainees_count,
                'trainees' => [],
                'loaded' => false,
            ];
        });

        $totalActiveTrainees = $courses->sum(fn($c) => $c->active_trainees_count);

        return Inertia::render('training/mentor-overview', [
            'courses' => $coursesMetadata->values(),
            'initialCourseId' => $courseId,
            'statistics' => [
                'activeTrainees' => $totalActiveTrainees,
                'claimedTrainees' => 0,
                'trainingSessions' => 0,
                'waitingList' => 0,
            ],
        ]);
    }
}