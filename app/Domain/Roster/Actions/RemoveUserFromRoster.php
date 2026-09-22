<?php

namespace App\Domain\Roster\Actions;

use App\Domain\Roster\Events\UserRemovedFromRoster;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\WaitingListEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RemoveUserFromRoster
{
    public function __construct(
        private readonly VatEudClientInterface $vatEudClient,
    ) {}

    public function execute(int $vatsimId, ?Carbon $lastSession = null, ?int $inactiveDays = null): bool
    {
        $success = $this->vatEudClient->removeRosterAndEndorsements($vatsimId);

        if (! $success) {
            Log::error('Roster removal failed at VATEUD', [
                'vatsim_id' => $vatsimId,
                'last_session' => $lastSession?->toIso8601String(),
                'inactive_days' => $inactiveDays,
            ]);

            return false;
        }

        WaitingListEntry::whereHas('user', fn ($q) => $q->where('vatsim_id', $vatsimId))->delete();

        event(new UserRemovedFromRoster($vatsimId, $lastSession, $inactiveDays));

        Log::warning("ROSTER REMOVAL COMPLETE: {$vatsimId}", [
            'last_session' => $lastSession?->toIso8601String(),
            'inactive_days' => $inactiveDays,
        ]);

        return true;
    }
}
