<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\TraineeUnclaimed;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UnclaimTrainee
{
    public function execute(Course $course, User $trainee, User $mentor): void
    {
        DB::table('course_trainees')
            ->where('course_id', $course->id)
            ->where('user_id', $trainee->id)
            ->update([
                'claimed_by_mentor_id' => null,
                'claimed_at'           => null,
            ]);

        event(new TraineeUnclaimed($course, $trainee, $mentor));
    }
}