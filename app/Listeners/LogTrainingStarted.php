<?php

namespace App\Listeners;

use App\Domain\Training\Events\TrainingStarted;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogTrainingStarted
{
    public function handle(TrainingStarted $event): void
    {
        ActivityLog::create([
            'action'      => 'training.started',
            'model_type'  => $event->course::class,
            'model_id'    => $event->course->id,
            'description' => "{$event->mentor->name} started training for {$event->trainee->name} in {$event->course->name}",
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