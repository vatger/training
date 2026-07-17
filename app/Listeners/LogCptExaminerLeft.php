<?php

namespace App\Listeners;

use App\Domain\Cpt\Events\CptExaminerLeft;
use App\Models\ActivityLog;

class LogCptExaminerLeft
{
    public function handle(CptExaminerLeft $event): void
    {
        $trainee = $event->cpt->trainee;
        $course  = $event->cpt->course;

        ActivityLog::create([
            'action'      => 'cpt.examiner_left',
            'model_type'  => $event->cpt::class,
            'model_id'    => $event->cpt->id,
            'description' => "{$event->examiner->name} cancelled as examiner for {$trainee->name}'s CPT in {$course->name}",
            'user_id'     => $event->examiner->id,
            'properties'  => [
                'cpt_id'        => $event->cpt->id,
                'trainee_id'    => $trainee->id,
                'trainee_name'  => $trainee->name,
                'course_id'     => $course->id,
                'course_name'   => $course->name,
                'position'      => $course->position,
                'examiner_id'   => $event->examiner->id,
                'examiner_name' => $event->examiner->name,
                'date'          => $event->cpt->date?->toIso8601String(),
            ],
        ]);
    }
}