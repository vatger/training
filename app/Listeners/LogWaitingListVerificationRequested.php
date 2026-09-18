<?php

namespace App\Listeners;

use App\Domain\WaitingList\Events\WaitingListVerificationRequested;
use App\Models\ActivityLog;

class LogWaitingListVerificationRequested
{
    public function handle(WaitingListVerificationRequested $event): void
    {
        ActivityLog::create([
            'action' => 'waiting_list.verification_requested',
            'model_type' => $event->user::class,
            'model_id' => $event->user->id,
            'description' => "Requested waiting list interest confirmation from {$event->user->name}",
            'user_id' => null,
            'properties' => [
                'user_id' => $event->user->id,
                'user_name' => $event->user->name,
                'course_ids' => $event->entries->pluck('course_id')->values()->all(),
                'course_names' => $event->entries->pluck('course.name')->values()->all(),
            ],
        ]);
    }
}
