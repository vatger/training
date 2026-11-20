<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\TraineeAddedToCourse;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Integrations\Moodle\MoodleEnrollmentException;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Support\Facades\DB;

class AddTraineeToCourse
{
    public function __construct(private readonly MoodleClientInterface $moodleClient) {}

    public function execute(Course $course, User $trainee, User $mentor): void
    {
        $wasReactivated = DB::transaction(function () use ($course, $trainee, $mentor) {
            WaitingListEntry::where('user_id', $trainee->id)
                ->where('course_id', $course->id)
                ->delete();

            $wasReactivated = DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->where('user_id', $trainee->id)
                ->whereNotNull('completed_at')
                ->exists();

            if ($wasReactivated) {
                DB::table('course_trainees')
                    ->where('course_id', $course->id)
                    ->where('user_id', $trainee->id)
                    ->update([
                        'completed_at' => null,
                        'status' => 'active',
                        'claimed_by_mentor_id' => $mentor->id,
                        'claimed_at' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
                $course->activeTrainees()->attach($trainee->id, [
                    'claimed_by_mentor_id' => $mentor->id,
                    'claimed_at' => now(),
                ]);

                $this->enrollInMoodle($trainee, $course);
            }

            return $wasReactivated;
        });

        event(new TraineeAddedToCourse($course, $trainee, $mentor, $wasReactivated));
    }

    /**
     * @throws MoodleEnrollmentException if the trainee could not be enrolled in every course
     */
    private function enrollInMoodle(User $trainee, Course $course): void
    {
        $failed = collect($course->moodle_course_ids ?? [])
            ->reject(fn ($courseId) => $this->moodleClient->enrollUser($trainee->vatsim_id, (int) $courseId))
            ->map(fn ($courseId) => (int) $courseId)
            ->values()
            ->all();

        if ($failed !== []) {
            throw MoodleEnrollmentException::forCourses($trainee->vatsim_id, $failed);
        }
    }
}
