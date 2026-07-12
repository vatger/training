<?php

namespace App\Listeners;

use App\Domain\Gdpr\Events\UserDeleted;
use App\Models\ActivityLog;

class LogUserDeleted
{
    public function handle(UserDeleted $event): void
    {
        ActivityLog::create([
            'action'      => 'gdpr.deletion',
            'model_type'  => $event->user::class,
            'model_id'    => $event->user->id,
            'description' => "GDPR deletion for user {$event->user->name} (VATSIM ID: {$event->user->vatsim_id})",
            'user_id'     => null,
            'properties'  => [
                'vatsim_id'  => $event->user->vatsim_id,
                'user_name'  => $event->user->name,
                'user_email' => $event->user->email,
                'ip_address' => $event->ipAddress,
            ],
        ]);
    }
}
