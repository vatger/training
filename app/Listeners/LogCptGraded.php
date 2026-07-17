<?php

namespace App\Listeners;

use App\Domain\Cpt\Events\CptGraded;
use App\Models\ActivityLog;

class LogCptGraded
{
    public function handle(CptGraded $event): void
    {
        $result  = $event->passed ? 'passed' : 'failed';
        $trainee = $event->cpt->trainee;
        $course  = $event->cpt->course;

        ActivityLog::create([
            'action'      => "cpt.graded_{$result}",
            'model_type'  => $event->cpt::class,
            'model_id'    => $event->cpt->id,
            'description' => "{$event->grader->name} marked {$trainee->name}'s CPT in {$course->name} as {$result}",
            'user_id'     => $event->grader->id,
            'properties'  => [
                'cpt_id'      => $event->cpt->id,
                'trainee_id'  => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id'   => $course->id,
                'course_name' => $course->name,
                'position'    => $course->position,
                'grader_id'   => $event->grader->id,
                'grader_name' => $event->grader->name,
                'result'      => $result,
                'passed'      => $event->passed,
                'date'        => $event->cpt->date?->toIso8601String(),
            ],
        ]);
    }
}