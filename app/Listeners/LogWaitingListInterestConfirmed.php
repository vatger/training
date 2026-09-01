<?php

namespace App\Listeners;

use App\Domain\WaitingList\Events\WaitingListInterestConfirmed;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogWaitingListInterestConfirmed
{
    public function handle(WaitingListInterestConfirmed $event): void
    {
        ActivityLog::create([
            'action' => 'waiting_list.interest_confirmed',
            'model_type' => $event->entry::class,
            'model_id' => $event->entry->id,
            'description' => "{$event->user->name} confirmed interest in waiting list for {$event->course->name}",
            'user_id' => $event->user->id,
            'properties' => [
                'course_id' => $event->course->id,
                'course_name' => $event->course->name,
                'user_id' => $event->user->id,
                'user_name' => $event->user->name,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
