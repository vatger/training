<?php

namespace App\Domain\Cpt\Actions;

use App\Domain\Cpt\Events\CptLocalJoined;
use App\Models\Cpt;
use App\Models\User;
use App\Services\CptNotificationService;

class JoinCptAsLocal
{
    public function __construct(
        private readonly CptNotificationService $notifications,
    ) {}

    public function execute(Cpt $cpt, User $local): void
    {
        $wasConfirmed = $cpt->confirmed;

        $cpt->update(['local_id' => $local->id]);
        $cpt->refresh();

        if (! $wasConfirmed && $cpt->confirmed) {
            $this->notifications->broadcastConfirmedCpts();
        }

        event(new CptLocalJoined($cpt, $local));
    }
}
