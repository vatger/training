<?php

namespace App\Domain\Cpt\Actions;

use App\Domain\Cpt\Events\CptLocalLeft;
use App\Models\Cpt;
use App\Models\User;
use App\Services\CptNotificationService;

class LeaveCptAsLocal
{
    public function __construct(
        private readonly CptNotificationService $notifications,
    ) {}

    public function execute(Cpt $cpt, User $local): void
    {
        $wasConfirmed = $cpt->confirmed;

        $cpt->update(['local_id' => null]);
        $cpt->refresh();

        if ($wasConfirmed && ! $cpt->confirmed) {
            $this->notifications->broadcastConfirmedCpts();
        }

        $this->notifications->notifyUnassignment($cpt, 'local', $local);

        event(new CptLocalLeft($cpt, $local));
    }
}
