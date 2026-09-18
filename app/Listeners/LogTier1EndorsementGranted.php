<?php

namespace App\Listeners;

use App\Domain\Endorsement\Events\Tier1EndorsementGranted;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogTier1EndorsementGranted
{
    public function handle(Tier1EndorsementGranted $event): void
    {
        ActivityLog::create([
            'action' => 'endorsement.tier1.granted',
            'model_type' => $event->trainee::class,
            'model_id' => $event->trainee->id,
            'description' => "{$event->mentor->name} granted {$event->position} Tier 1 endorsement to {$event->trainee->name}",
            'user_id' => $event->mentor->id,
            'properties' => [
                'position' => $event->position,
                'trainee_id' => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'mentor_id' => $event->mentor->id,
                'mentor_name' => $event->mentor->name,
                'type' => 'tier1',
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
