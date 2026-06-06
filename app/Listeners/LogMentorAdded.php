<?php

namespace App\Listeners;

use App\Domain\Training\Events\MentorAdded;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogMentorAdded
{
    public function handle(MentorAdded $event): void
    {
        ActivityLog::create([
            'action'      => 'mentor.added',
            'model_type'  => $event->course::class,
            'model_id'    => $event->course->id,
            'description' => "{$event->addingUser->name} added {$event->newMentor->name} as mentor for {$event->course->name}",
            'user_id'     => $event->addingUser->id,
            'properties'  => [
                'course_id'       => $event->course->id,
                'course_name'     => $event->course->name,
                'new_mentor_id'   => $event->newMentor->id,
                'new_mentor_name' => $event->newMentor->name,
                'adding_user_id'  => $event->addingUser->id,
                'adding_user_name' => $event->addingUser->name,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}