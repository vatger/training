<?php

namespace App\Listeners;

use App\Domain\Endorsement\Events\EndorsementRemoved;
use App\Models\ActivityLog;
use App\Models\User;

class LogEndorsementRemoved
{
    public function handle(EndorsementRemoved $event): void
    {
        $trainee = User::where('vatsim_id', $event->activity->vatsim_id)->first();

        ActivityLog::create([
            'action' => 'endorsement.removed',
            'model_type' => $trainee ? $trainee::class : null,
            'model_id' => $trainee?->id,
            'description' => "Tier 1 endorsement for {$event->activity->position} removed from user {$event->activity->vatsim_id} due to inactivity",
            'user_id' => null,
            'properties' => [
                'endorsement_id' => $event->activity->endorsement_id,
                'vatsim_id' => $event->activity->vatsim_id,
                'position' => $event->activity->position,
                'activity_minutes' => $event->activityMinutes,
                'removed_by' => 'system',
            ],
        ]);
    }
}
