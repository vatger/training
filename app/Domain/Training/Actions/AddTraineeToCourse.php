<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\TraineeAddedToCourse;
use App\Integrations\Moodle\MoodleClient;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddTraineeToCourse
{
    public function __construct(private readonly MoodleClient $moodleClient)
    {
    }

    public function execute(Course $course, User $trainee, User $mentor): void
    {
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
                    'completed_at'         => null,
                    'status'               => 'active',
                    'claimed_by_mentor_id' => $mentor->id,
                    'claimed_at'           => now(),
                    'updated_at'           => now(),
                ]);
        } else {
            $course->activeTrainees()->attach($trainee->id, [
                'claimed_by_mentor_id' => $mentor->id,
                'claimed_at'           => now(),
            ]);

            $this->enrollInMoodle($trainee, $course);
        }

        event(new TraineeAddedToCourse($course, $trainee, $mentor, $wasReactivated));
    }

    private function enrollInMoodle(User $trainee, Course $course): void
    {
        if (empty($course->moodle_course_ids)) {
            return;
        }

        try {
            foreach ($course->moodle_course_ids as $courseId) {
                $this->moodleClient->enrollUser($trainee->vatsim_id, $courseId);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to enroll trainee in Moodle courses', [
                'trainee_id' => $trainee->id,
                'course_id'  => $course->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}