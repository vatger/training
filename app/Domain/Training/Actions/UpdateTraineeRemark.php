<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\TraineeRemarkUpdated;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateTraineeRemark
{
    public function execute(Course $course, User $trainee, User $mentor, string $remark): void
    {
        DB::table('course_trainees')
            ->where('course_id', $course->id)
            ->where('user_id', $trainee->id)
            ->update([
                'remarks' => $remark,
                'remark_author_id' => $mentor->id,
                'remark_updated_at' => now(),
            ]);

        event(new TraineeRemarkUpdated($course, $trainee, $mentor, $remark));
    }
}
