<?php

namespace App\Listeners;

use App\Domain\Cpt\Events\CptCreated;
use App\Models\ActivityLog;

class LogCptCreated
{
    public function handle(CptCreated $event): void
    {
        $description = "{$event->creator->name} created CPT for {$event->trainee->name} in {$event->course->name}";

        if ($event->examiner) {
            $description .= " with examiner {$event->examiner->name}";
        }

        if ($event->local) {
            $description .= " and local mentor {$event->local->name}";
        }

        $properties = [
            'cpt_id' => $event->cpt->id,
            'trainee_id' => $event->trainee->id,
            'trainee_name' => $event->trainee->name,
            'course_id' => $event->course->id,
            'course_name' => $event->course->name,
            'position' => $event->course->position,
            'date' => $event->cpt->date?->toIso8601String(),
            'creator_id' => $event->creator->id,
            'creator_name' => $event->creator->name,
        ];

        if ($event->examiner) {
            $properties['examiner_id'] = $event->examiner->id;
            $properties['examiner_name'] = $event->examiner->name;
        }

        if ($event->local) {
            $properties['local_id'] = $event->local->id;
            $properties['local_name'] = $event->local->name;
        }

        ActivityLog::create([
            'action' => 'cpt.created',
            'model_type' => $event->cpt::class,
            'model_id' => $event->cpt->id,
            'description' => $description,
            'user_id' => $event->creator->id,
            'properties' => $properties,
        ]);
    }
}
