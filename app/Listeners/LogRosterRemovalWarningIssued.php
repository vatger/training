<?php

namespace App\Listeners;

use App\Domain\Roster\Events\RosterRemovalWarningIssued;
use App\Models\ActivityLog;
use App\Models\User;

class LogRosterRemovalWarningIssued
{
    public function handle(RosterRemovalWarningIssued $event): void
    {
        $user = User::where('vatsim_id', $event->vatsimId)->first();

        ActivityLog::create([
            'action' => 'roster.notified',
            'model_type' => $user ? $user::class : null,
            'model_id' => $user?->id,
            'description' => "Notified roster removal for {$event->vatsimId}",
            'user_id' => null,
            'properties' => [
                'vatsim_id' => $event->vatsimId,
                'removal_date' => $event->entry->removal_date?->toIso8601String(),
            ],
        ]);
    }
}