<?php

namespace App\Listeners;

use App\Domain\Cpt\Events\CptDeleted;
use App\Models\ActivityLog;

class LogCptDeleted
{
    public function handle(CptDeleted $event): void
    {
        ActivityLog::create([
            'action' => 'cpt.deleted',
            'model_type' => $event->cpt::class,
            'model_id' => $event->cpt->id,
            'description' => "{$event->deleter->name} deleted CPT for {$event->trainee->name} in {$event->course->name}",
            'user_id' => $event->deleter->id,
            'properties' => [
                'cpt_id' => $event->cpt->id,
                'trainee_id' => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'course_id' => $event->course->id,
                'course_name' => $event->course->name,
                'position' => $event->course->position,
                'deleter_id' => $event->deleter->id,
                'deleter_name' => $event->deleter->name,
                'date' => $event->cpt->date?->toIso8601String(),
                'was_graded' => $event->cpt->passed !== null,
                'had_log' => $event->cpt->log_uploaded,
            ],
        ]);
    }
}
