<?php

namespace App\Listeners;

use App\Domain\Training\Events\CourseFinished;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogCourseFinished
{
    public function handle(CourseFinished $event): void
    {
        ActivityLog::create([
            'action'      => 'course.finished',
            'model_type'  => $event->course::class,
            'model_id'    => $event->course->id,
            'description' => "{$event->mentor->name} marked {$event->course->name} as finished for {$event->trainee->name}",
            'user_id'     => $event->mentor->id,
            'properties'  => [
                'course_id'    => $event->course->id,
                'course_name'  => $event->course->name,
                'trainee_id'   => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'mentor_id'    => $event->mentor->id,
                'mentor_name'  => $event->mentor->name,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}