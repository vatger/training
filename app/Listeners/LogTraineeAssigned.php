<?php

namespace App\Listeners;

use App\Domain\Training\Events\TraineeAssigned;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogTraineeAssigned
{
    public function handle(TraineeAssigned $event): void
    {
        ActivityLog::create([
            'action'      => 'trainee.assigned',
            'model_type'  => $event->course::class,
            'model_id'    => $event->course->id,
            'description' => "{$event->assigningMentor->name} assigned {$event->trainee->name} to {$event->newMentor->name} for {$event->course->name}",
            'user_id'     => $event->assigningMentor->id,
            'properties'  => [
                'course_id'            => $event->course->id,
                'course_name'          => $event->course->name,
                'trainee_id'           => $event->trainee->id,
                'trainee_name'         => $event->trainee->name,
                'new_mentor_id'        => $event->newMentor->id,
                'new_mentor_name'      => $event->newMentor->name,
                'assigning_mentor_id'  => $event->assigningMentor->id,
                'assigning_mentor_name' => $event->assigningMentor->name,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}