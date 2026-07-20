<?php

namespace App\Listeners;

use App\Domain\Solo\Events\SoloExtended;
use App\Models\ActivityLog;

class LogSoloExtended
{
    public function handle(SoloExtended $event): void
    {
        ActivityLog::create([
            'action' => 'solo.extended',
            'model_type' => $event->trainee::class,
            'model_id' => $event->trainee->id,
            'description' => "{$event->mentor->name} extended solo endorsement for {$event->position} for {$event->trainee->name} (new expiry: {$event->newExpiryDate})",
            'user_id' => $event->mentor->id,
            'properties' => [
                'position' => $event->position,
                'trainee_id' => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'mentor_id' => $event->mentor->id,
                'mentor_name' => $event->mentor->name,
                'new_expiry_date' => $event->newExpiryDate,
            ],
        ]);
    }
}
