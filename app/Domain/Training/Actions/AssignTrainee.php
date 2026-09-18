<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\TraineeAssigned;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignTrainee
{
    public function execute(Course $course, User $trainee, User $newMentor, User $assigningMentor): void
    {
        DB::table('course_trainees')
            ->where('course_id', $course->id)
            ->where('user_id', $trainee->id)
            ->update([
                'claimed_by_mentor_id' => $newMentor->id,
                'claimed_at' => now(),
            ]);

        event(new TraineeAssigned($course, $trainee, $newMentor, $assigningMentor));
    }
}
