<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TraineeOrderController extends Controller
{
    public function updateOrder(Request $request)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'trainee_ids' => 'required|array',
            'trainee_ids.*' => 'integer|exists:users,id',
        ]);

        $course = \App\Models\Course::findOrFail($request->course_id);

        if (! $user->is_superuser && ! $user->is_admin && ! $user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'You cannot modify this course'], 403);
        }

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->trainee_ids as $index => $traineeId) {
                    DB::table('course_trainees')
                        ->where('course_id', $request->course_id)
                        ->where('user_id', $traineeId)
                        ->update(['custom_order' => $index + 1, 'updated_at' => now()]);
                }
            });

            Log::info('Trainee order updated', [
                'mentor_id' => $user->id,
                'course_id' => $request->course_id,
                'trainee_count' => count($request->trainee_ids),
            ]);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (\Exception $e) {
            Log::error('Error updating trainee order', [
                'mentor_id' => $user->id,
                'course_id' => $request->course_id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to update trainee order']);
        }
    }

    public function resetOrder(Request $request)
    {
        $user = $request->user();

        if (! $user->isMentor() && ! $user->is_superuser) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $course = \App\Models\Course::findOrFail($request->course_id);

        if (! $user->is_superuser && ! $user->is_admin && ! $user->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return response()->json(['error' => 'You cannot modify this course'], 403);
        }

        try {
            DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->update(['custom_order' => null, 'updated_at' => now()]);

            Log::info('Trainee order reset', ['mentor_id' => $user->id, 'course_id' => $course->id]);

            return redirect()->route('overview.index', ['last_course_id' => $course->id]);
        } catch (\Exception $e) {
            Log::error('Error resetting trainee order', [
                'mentor_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to reset trainee order']);
        }
    }
}
