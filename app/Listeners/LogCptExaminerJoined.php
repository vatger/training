<?php

namespace App\Listeners;

use App\Domain\Cpt\Events\CptExaminerJoined;
use App\Models\ActivityLog;

class LogCptExaminerJoined
{
    public function handle(CptExaminerJoined $event): void
    {
        $trainee = $event->cpt->trainee;
        $course  = $event->cpt->course;

        ActivityLog::create([
            'action'      => 'cpt.examiner_joined',
            'model_type'  => $event->cpt::class,
            'model_id'    => $event->cpt->id,
            'description' => "{$event->examiner->name} signed up as examiner for {$trainee->name}'s CPT in {$course->name}",
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