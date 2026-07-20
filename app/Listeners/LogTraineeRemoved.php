<?php

namespace App\Listeners;

use App\Domain\Training\Events\TraineeRemoved;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogTraineeRemoved
{
    public function handle(TraineeRemoved $event): void
    {
        ActivityLog::create([
            'action' => 'trainee.removed',
            'model_type' => $event->course::class,
            'model_id' => $event->course->id,
            'description' => "{$event->mentor->name} removed {$event->trainee->name} from {$event->course->name}",
            'user_id' => $event->mentor->id,
            'properties' => [
                'course_id' => $event->course->id,
                'course_name' => $event->course->name,
                'trainee_id' => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'mentor_id' => $event->mentor->id,
                'mentor_name' => $event->mentor->name,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
