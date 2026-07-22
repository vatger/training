<?php

namespace App\Listeners;

use App\Domain\Training\Events\FamiliarisationAdded;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class LogFamiliarisationAdded
{
    public function handle(FamiliarisationAdded $event): void
    {
        ActivityLog::create([
            'action' => 'familiarisation.added',
            'model_type' => $event->trainee::class,
            'model_id' => $event->trainee->id,
            'description' => "{$event->mentor->name} granted {$event->sectorName} ({$event->fir}) familiarisation to {$event->trainee->name} via {$event->course->name} completion",
            'user_id' => $event->mentor->id,
            'properties' => [
                'trainee_id' => $event->trainee->id,
                'trainee_name' => $event->trainee->name,
                'sector_id' => $event->sectorId,
                'sector_name' => $event->sectorName,
                'fir' => $event->fir,
                'mentor_id' => $event->mentor->id,
                'mentor_name' => $event->mentor->name,
                'course_id' => $event->course->id,
                'course_name' => $event->course->name,
                'via_course_completion' => true,
            ],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
