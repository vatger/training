<?php

namespace App\Domain\Roster\Actions;

use App\Domain\Roster\Events\UserRemovedFromRoster;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\WaitingListEntry;
use Illuminate\Support\Facades\Log;

class RemoveUserFromRoster
{
    public function __construct(
        private readonly VatEudClientInterface $vatEudClient,
    ) {}

    public function execute(int $vatsimId): void
    {
        $success = $this->vatEudClient->removeRosterAndEndorsements($vatsimId);

        if (! $success) {
            Log::error('Roster removal failed at VATEUD', ['vatsim_id' => $vatsimId]);

            return;
        }

        WaitingListEntry::whereHas('user', fn ($q) => $q->where('vatsim_id', $vatsimId))->delete();

        event(new UserRemovedFromRoster($vatsimId));

        Log::warning("ROSTER REMOVAL COMPLETE: {$vatsimId}");
    }
}
