<?php

namespace App\Listeners;

use App\Domain\WaitingList\Events\WaitingListLeft;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogWaitingListLeft
{
    public function handle(WaitingListLeft $event): void
    {
        ActivityLog::create([
            'action' => 'waiting_list.left',
            'model_type' => $event->entry::class,
            'model_id' => $event->entry->id,
            'description' => "{$event->user->name} left waiting list for {$event->course->name} (was position {$event->entry->position_in_queue})",
            'user_id' => $event->user->id,
            'properties' => [
                'course_id' => $event->course->id,
                'course_name' => $event->course->name,
                'user_id' => $event->user->id,
                'user_name' => $event->user->name,
                'position_in_queue' => $event->entry->position_in_queue ?? null,
                'days_waited' => $event->entry->date_added ? now()->diffInDays($event->entry->date_added) : null,
                'activity' => $event->entry->activity ?? 0,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
