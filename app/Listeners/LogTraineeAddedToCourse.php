<?php

namespace App\Listeners;

use App\Domain\Training\Events\TraineeAddedToCourse;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogTraineeAddedToCourse
{
    public function handle(TraineeAddedToCourse $event): void
    {
        $description = "{$event->mentor->name} added {$event->trainee->name} to {$event->course->name}";
        if ($event->wasReactivated) {
            $description .= ' (reactivated)';
        }

        ActivityLog::create([
            'action' => 'trainee.added_to_course',
            'model_type' => $event->course::class,
            'model_id' => $event->course->id,
            'description' => $description,
            'user_id' => $event->mentor->id,
            'properties' => [
                'trainee_id' => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'course_id' => $event->course->id,
                'course_name' => $event->course->name,
                'mentor_id' => $event->mentor->id,
                'mentor_name' => $event->mentor->name,
                'was_reactivated' => $event->wasReactivated,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
