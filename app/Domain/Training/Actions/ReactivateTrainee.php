<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\TraineeReactivated;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReactivateTrainee
{
    public function execute(Course $course, User $trainee, User $mentor): void
    {
        DB::table('course_trainees')
            ->where('course_id', $course->id)
            ->where('user_id', $trainee->id)
            ->update([
                'completed_at' => null,
                'status' => 'active',
                'claimed_by_mentor_id' => $mentor->id,
                'claimed_at' => now(),
            ]);

        event(new TraineeReactivated($course, $trainee, $mentor));
    }
}
