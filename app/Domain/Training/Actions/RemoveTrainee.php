<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\TraineeRemoved;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RemoveTrainee
{
    public function execute(Course $course, User $trainee, User $mentor): void
    {
        DB::table('course_trainees')
            ->where('course_id', $course->id)
            ->where('user_id', $trainee->id)
            ->update([
                'completed_at' => now(),
                'status' => 'removed',
                'claimed_by_mentor_id' => null,
                'claimed_at' => null,
            ]);

        event(new TraineeRemoved($course, $trainee, $mentor));
    }
}
