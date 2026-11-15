<?php

namespace App\Listeners;

use App\Domain\Gdpr\Events\UserDeleted;
use App\Models\ActivityLog;

class LogUserDeleted
{
    /**
     * Deliberately records no name or email: the entry has to outlive the erasure.
     */
    public function handle(UserDeleted $event): void
    {
        ActivityLog::create([
            'action' => 'gdpr.deletion',
            'model_type' => $event->request::class,
            'model_id' => $event->request->id,
            'description' => "GDPR deletion approved for VATSIM ID {$event->request->vatsim_id} (request #{$event->request->id})",
            'user_id' => $event->approver?->id,
            'properties' => [
                'vatsim_id' => $event->request->vatsim_id,
                'request_id' => $event->request->id,
                'approved_by_id' => $event->approver?->id,
                'ip_address' => $event->ipAddress,
            ],
            'ip_address' => $event->ipAddress,
        ]);
    }
}
