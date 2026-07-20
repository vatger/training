<?php

namespace App\Listeners;

use App\Domain\Training\Events\MentorRemoved;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogMentorRemoved
{
    public function handle(MentorRemoved $event): void
    {
        ActivityLog::create([
            'action' => 'mentor.removed',
            'model_type' => $event->course::class,
            'model_id' => $event->course->id,
            'description' => "{$event->removingUser->name} removed {$event->removedMentor->name} as mentor from {$event->course->name}",
            'user_id' => $event->removingUser->id,
            'properties' => [
                'course_id' => $event->course->id,
                'course_name' => $event->course->name,
                'removed_mentor_id' => $event->removedMentor->id,
                'removed_mentor_name' => $event->removedMentor->name,
                'removing_user_id' => $event->removingUser->id,
                'removing_user_name' => $event->removingUser->name,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
