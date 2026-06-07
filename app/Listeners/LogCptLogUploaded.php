<?php

namespace App\Listeners;

use App\Domain\Cpt\Events\CptLogUploaded;
use App\Models\ActivityLog;

class LogCptLogUploaded
{
    public function handle(CptLogUploaded $event): void
    {
        $trainee = $event->cpt->trainee;
        $course  = $event->cpt->course;

        ActivityLog::create([
            'action'      => 'cpt.log_uploaded',
            'model_type'  => $event->cpt::class,
            'model_id'    => $event->cpt->id,
            'description' => "{$event->uploader->name} uploaded CPT log for {$trainee->name}'s CPT in {$course->name}",
            'user_id'     => $event->uploader->id,
            'properties'  => [
                'cpt_id'        => $event->cpt->id,
                'cpt_log_id'    => $event->log->id,
                'trainee_id'    => $trainee->id,
                'trainee_name'  => $trainee->name,
                'course_id'     => $course->id,
                'course_name'   => $course->name,
                'position'      => $course->position,
                'uploader_id'   => $event->uploader->id,
                'uploader_name' => $event->uploader->name,
                'file_name'     => $event->log->file_name,
                'date'          => $event->cpt->date?->toIso8601String(),
            ],
        ]);
    }
}