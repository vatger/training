<?php

namespace App\Listeners;

use App\Domain\Endorsement\Events\Tier2EndorsementGranted;
use App\Models\ActivityLog;

class LogTier2EndorsementGranted
{
    public function handle(Tier2EndorsementGranted $event): void
    {
        ActivityLog::create([
            'action' => 'endorsement.tier2.granted',
            'model_type' => $event->trainee::class,
            'model_id' => $event->trainee->id,
            'description' => "{$event->trainee->name} was granted {$event->tier2Endorsement->position} Tier 2 endorsement",
            'user_id' => $event->trainee->id,
            'properties' => [
                'position' => $event->tier2Endorsement->position,
                'trainee_id' => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'type' => 'tier2',
            ],
        ]);
    }
}