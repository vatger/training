<?php

namespace App\Domain\Roster\Actions;

use App\Domain\Roster\Events\RosterRemovalWarningIssued;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\RosterEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckUserRosterStatus
{
    private const WARNING_THRESHOLD = 330;

    private const REMOVAL_THRESHOLD = 365;

    private const GRACE_DAYS = 35;

    public function __construct(
        private readonly VatEudClientInterface $vatEudClient,
        private readonly SendRosterRemovalWarning $sendWarning,
        private readonly RemoveUserFromRoster $removeUser,
    ) {}

    public function execute(int $vatsimId): void
    {
        $entry = RosterEntry::firstOrCreate(
            ['user_id' => $vatsimId],
            ['removal_date' => null],
        );

        $lastSession = $this->fetchLastSession($vatsimId);

        if ($lastSession instanceof Carbon && $lastSession->year > 2000) {
            $entry->last_session = $lastSession;
            $entry->save();
        }

        if (! $entry->last_session) {
            return;
        }

        $inactiveDays = $entry->last_session->diffInDays(now());

        if ($inactiveDays < self::WARNING_THRESHOLD) {
            if ($entry->removal_date) {
                $entry->removal_date = null;
                $entry->save();
            }

            return;
        }

        if ($inactiveDays >= self::WARNING_THRESHOLD && ! $entry->removal_date) {
            $this->sendWarning->execute($vatsimId);

            $entry->removal_date = now()->addDays(self::GRACE_DAYS);
            $entry->save();

            event(new RosterRemovalWarningIssued($vatsimId, $entry));

            return;
        }

        if (
            $inactiveDays >= self::REMOVAL_THRESHOLD &&
            $entry->removal_date &&
            now()->gte($entry->removal_date)
        ) {
            $this->removeUser->execute($vatsimId);
            $entry->delete();
        }
    }

    private function fetchLastSession(int $vatsimId): ?Carbon
    {
        try {
            return $this->vatEudClient->getLastGermanSession($vatsimId);
        } catch (\Throwable $e) {
            Log::warning('Failed fetching last session', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
