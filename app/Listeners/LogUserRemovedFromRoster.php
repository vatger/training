<?php

namespace App\Listeners;

use App\Domain\Roster\Events\UserRemovedFromRoster;
use App\Models\ActivityLog;
use App\Models\User;

class LogUserRemovedFromRoster
{
    public function handle(UserRemovedFromRoster $event): void
    {
        $user = User::where('vatsim_id', $event->vatsimId)->first();

        ActivityLog::create([
            'action' => 'roster.removed',
            'model_type' => $user ? $user::class : null,
            'model_id' => $user?->id,
            'description' => "User {$event->vatsimId} removed from roster due to inactivity",
            'user_id' => null,
            'properties' => [
                'vatsim_id' => $event->vatsimId,
                'reason' => 'inactivity',
                'removed_by' => 'system',
            ],
        ]);
    }
}