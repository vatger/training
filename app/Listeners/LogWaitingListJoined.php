<?php

namespace App\Listeners;

use App\Domain\WaitingList\Events\WaitingListJoined;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogWaitingListJoined
{
    public function handle(WaitingListJoined $event): void
    {
        ActivityLog::create([
            'action'      => 'waiting_list.joined',
            'model_type'  => $event->entry::class,
            'model_id'    => $event->entry->id,
            'description' => "{$event->user->name} joined waiting list for {$event->course->name}",
            'user_id'     => $event->user->id,
            'properties'  => [
                'course_id'         => $event->course->id,
                'course_name'       => $event->course->name,
                'user_id'           => $event->user->id,
                'user_name'         => $event->user->name,
                'position_in_queue' => $event->entry->position_in_queue ?? null,
                'activity'          => $event->entry->activity ?? 0,
                'date_added'        => $event->entry->date_added?->toIso8601String(),
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}