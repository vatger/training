<?php

namespace App\Listeners;

use App\Domain\Endorsement\Events\EndorsementMarkedForRemoval;
use App\Models\ActivityLog;

class LogEndorsementMarkedForRemoval
{
    public function handle(EndorsementMarkedForRemoval $event): void
    {
        ActivityLog::create([
            'action' => 'endorsement.removed',
            'model_type' => $event->trainee ? $event->trainee::class : $event->activity::class,
            'model_id' => $event->trainee?->id ?? $event->activity->id,
            'description' => "{$event->actor->name} started the removal process for {$event->activity->position} endorsement"
                .($event->trainee ? " from {$event->trainee->name}" : ''),
            'user_id' => $event->actor->id,
            'properties' => [
                'position' => $event->activity->position,
                'trainee_id' => $event->trainee?->id,
                'trainee_name' => $event->trainee?->name,
                'mentor_id' => $event->actor->id,
                'mentor_name' => $event->actor->name,
                'reason' => 'Marked for removal due to low activity',
            ],
        ]);
    }
}
