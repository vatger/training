<?php

namespace App\Listeners;

use App\Domain\Cpt\Events\CptLocalLeft;
use App\Models\ActivityLog;

class LogCptLocalLeft
{
    public function handle(CptLocalLeft $event): void
    {
        $trainee = $event->cpt->trainee;
        $course = $event->cpt->course;

        ActivityLog::create([
            'action' => 'cpt.local_left',
            'model_type' => $event->cpt::class,
            'model_id' => $event->cpt->id,
            'description' => "{$event->local->name} cancelled as local mentor for {$trainee->name}'s CPT in {$course->name}",
            'user_id' => $event->local->id,
            'properties' => [
                'cpt_id' => $event->cpt->id,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'position' => $course->position,
                'local_id' => $event->local->id,
                'local_name' => $event->local->name,
                'date' => $event->cpt->date?->toIso8601String(),
            ],
        ]);
    }
}
