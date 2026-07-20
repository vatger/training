<?php

namespace App\Listeners;

use App\Domain\Training\Events\TraineeRemarkUpdated;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogTraineeRemarkUpdated
{
    public function handle(TraineeRemarkUpdated $event): void
    {
        ActivityLog::create([
            'action' => 'remarks.updated',
            'model_type' => $event->course::class,
            'model_id' => $event->course->id,
            'description' => "{$event->mentor->name} updated remarks for {$event->trainee->name} in {$event->course->name}",
            'user_id' => $event->mentor->id,
            'properties' => [
                'course_id' => $event->course->id,
                'course_name' => $event->course->name,
                'trainee_id' => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'mentor_id' => $event->mentor->id,
                'mentor_name' => $event->mentor->name,
                'remarks_length' => strlen($event->newRemarks),
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
