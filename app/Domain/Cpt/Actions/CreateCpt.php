<?php

namespace App\Domain\Cpt\Actions;

use App\Domain\Cpt\Events\CptCreated;
use App\Models\Cpt;
use App\Models\Course;
use App\Models\User;
use App\Services\CptNotificationService;

class CreateCpt
{
    public function __construct(
        private readonly CptNotificationService $notifications,
    ) {}

    public function execute(
        Course $course,
        User $trainee,
        string $date,
        User $creator,
        ?User $examiner = null,
        ?User $local = null,
    ): Cpt {
        $cpt = Cpt::create([
            'course_id'   => $course->id,
            'trainee_id'  => $trainee->id,
            'date'        => $date,
            'examiner_id' => $examiner?->id,
            'local_id'    => $local?->id,
            'created_by'  => $creator->id,
        ]);

        if ($cpt->confirmed) {
            $this->notifications->broadcastConfirmedCpts();
        }

        $this->notifications->notifyAvailableCpt($cpt);

        event(new CptCreated($cpt, $course, $trainee, $creator, $examiner, $local));

        return $cpt;
    }
}