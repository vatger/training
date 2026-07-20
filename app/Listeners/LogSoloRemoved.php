<?php

namespace App\Listeners;

use App\Domain\Solo\Events\SoloRemoved;
use App\Models\ActivityLog;

class LogSoloRemoved
{
    public function handle(SoloRemoved $event): void
    {
        ActivityLog::create([
            'action' => 'solo.removed',
            'model_type' => $event->trainee::class,
            'model_id' => $event->trainee->id,
            'description' => "{$event->mentor->name} removed solo endorsement for {$event->position} from {$event->trainee->name}",
            'user_id' => $event->mentor->id,
            'properties' => [
                'position' => $event->position,
                'trainee_id' => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'mentor_id' => $event->mentor->id,
                'mentor_name' => $event->mentor->name,
            ],
        ]);
    }
}
