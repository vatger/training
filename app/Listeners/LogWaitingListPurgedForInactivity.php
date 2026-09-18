<?php

namespace App\Listeners;

use App\Domain\WaitingList\Events\WaitingListPurgedForInactivity;
use App\Models\ActivityLog;

class LogWaitingListPurgedForInactivity
{
    public function handle(WaitingListPurgedForInactivity $event): void
    {
        ActivityLog::create([
            'action' => 'waiting_list.purged_for_inactivity',
            'model_type' => $event->entry::class,
            'model_id' => $event->entry->id,
            'description' => "{$event->user->name} was removed from waiting list for {$event->course->name} for not confirming interest",
            'user_id' => null,
            'properties' => [
                'course_id' => $event->course->id,
                'course_name' => $event->course->name,
                'user_id' => $event->user->id,
                'user_name' => $event->user->name,
            ],
        ]);
    }
}
