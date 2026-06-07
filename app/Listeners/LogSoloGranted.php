<?php

namespace App\Listeners;

use App\Domain\Solo\Events\SoloGranted;
use App\Models\ActivityLog;

class LogSoloGranted
{
    public function handle(SoloGranted $event): void
    {
        ActivityLog::create([
            'action'      => 'solo.granted',
            'model_type'  => $event->trainee::class,
            'model_id'    => $event->trainee->id,
            'description' => "{$event->mentor->name} granted solo endorsement for {$event->position} to {$event->trainee->name} (expires: {$event->expiryDate})",
            'user_id'     => $event->mentor->id,
            'properties'  => [
                'position'    => $event->position,
                'trainee_id'  => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'mentor_id'   => $event->mentor->id,
                'mentor_name' => $event->mentor->name,
                'expiry_date' => $event->expiryDate,
            ],
        ]);
    }
}