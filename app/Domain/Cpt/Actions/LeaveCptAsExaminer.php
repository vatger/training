<?php

namespace App\Domain\Cpt\Actions;

use App\Domain\Cpt\Events\CptExaminerLeft;
use App\Models\Cpt;
use App\Models\User;
use App\Services\CptNotificationService;

class LeaveCptAsExaminer
{
    public function __construct(
        private readonly CptNotificationService $notifications,
    ) {}

    public function execute(Cpt $cpt, User $examiner): void
    {
        $wasConfirmed = $cpt->confirmed;

        $cpt->update(['examiner_id' => null]);
        $cpt->refresh();

        if ($wasConfirmed && ! $cpt->confirmed) {
            $this->notifications->broadcastConfirmedCpts();
        }

        $this->notifications->notifyUnassignment($cpt, 'examiner', $examiner);

        event(new CptExaminerLeft($cpt, $examiner));
    }
}
