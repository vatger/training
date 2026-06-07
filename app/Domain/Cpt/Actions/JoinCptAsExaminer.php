<?php

namespace App\Domain\Cpt\Actions;

use App\Domain\Cpt\Events\CptExaminerJoined;
use App\Models\Cpt;
use App\Models\User;
use App\Services\CptNotificationService;

class JoinCptAsExaminer
{
    public function __construct(
        private readonly CptNotificationService $notifications,
    ) {}

    public function execute(Cpt $cpt, User $examiner): void
    {
        $wasConfirmed = $cpt->confirmed;

        $cpt->update(['examiner_id' => $examiner->id]);
        $cpt->refresh();

        if (!$wasConfirmed && $cpt->confirmed) {
            $this->notifications->broadcastConfirmedCpts();
        }

        event(new CptExaminerJoined($cpt, $examiner));
    }
}