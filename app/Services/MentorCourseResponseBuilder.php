<?php

namespace App\Services;

use App\Http\Controllers\MentorOverviewController;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MentorCourseResponseBuilder
{
    public function __construct(
        private MentorOverviewController $mentorOverviewController,
    ) {}

    public function build(Course $activeCourse, User $user): Response
    {
        $courses = $this->fetchAccessibleCourses($user);
        $sorted  = $this->sortCourses($courses);

        $coursesMetadata = $sorted->map(function ($course) use ($activeCourse, $user) {
            if ($course->id !== $activeCourse->id) {
                return $this->stubCourse($course);
            }

            try {
                $full = Course::find($course->id);
                if ($full) {
                    $data           = $this->mentorOverviewController->loadCourseData($full, $user);
                    $data['loaded'] = true;
                    return $data;
                }
            } catch (\Exception $e) {
                \Log::error('MentorCourseResponseBuilder: failed to load course', [
                    'course_id' => $course->id,
                    'error'     => $e->getMessage(),
                ]);
            }

            return $this->stubCourse($course);
        });

        return Inertia::render('training/mentor-overview', [
            'courses'        => $coursesMetadata->values(),
            'initialCourseId' => $activeCourse->id,
            'statistics'     => $this->buildStatistics($user, $sorted),
        ]);
    }

    private function fetchAccessibleCourses(User $user): \Illuminate\Support\Collection
    {
        if ($user->is_superuser || $user->is_admin) {
            return Course::select(['id', 'name', 'position', 'type', 'solo_station', 'mentor_group_id'])
                ->withCount('activeTrainees')
                ->get();
        }

        return $user->mentorCourses()
            ->select(['courses.id', 'courses.name', 'courses.position', 'courses.type', 'courses.solo_station', 'courses.mentor_group_id'])
            ->withCount('activeTrainees')
            ->get();
    }

    private function sortCourses(\Illuminate\Support\Collection $courses): \Illuminate\Support\Collection
    {
        $positionOrder = ['GND' => 1, 'TWR' => 2, 'APP' => 3];

        $nonCtr = $courses
            ->filter(fn($c) => $c->position !== 'CTR')
            ->sortBy(fn($c) => $positionOrder[$c->position] ?? 999)
            ->sortBy('name');

        $ctr = $courses
            ->filter(fn($c) => $c->position === 'CTR')
            ->sortBy('name');

        return $nonCtr->concat($ctr)->values();
    }

    private function stubCourse(Course $course): array
    {
        return [
            'id'             => $course->id,
            'name'           => $course->name,
            'position'       => $course->position,
            'type'           => $course->type,
            'soloStation'    => $course->solo_station,
            'activeTrainees' => $course->active_trainees_count,
            'trainees'       => [],
            'loaded'         => false,
        ];
    }

    private function buildStatistics(User $user, \Illuminate\Support\Collection $courses): array
    {
        $courseIds = $courses->pluck('id')->toArray();

        return [
            'activeTrainees' => $courses->sum(fn($c) => $c->active_trainees_count),
            'claimedTrainees' => DB::table('course_trainees')
                ->whereIn('course_id', $courseIds)
                ->where('claimed_by_mentor_id', $user->id)
                ->whereNull('completed_at')
                ->count(),
            'trainingSessions' => DB::table('training_logs')
                ->whereIn('course_id', $courseIds)
                ->where('mentor_id', $user->id)
                ->where('session_date', '>=', now()->subDays(30))
                ->count(),
            'waitingList' => DB::table('waiting_list_entries')
                ->whereIn('course_id', $courseIds)
                ->count(),
        ];
    }
}