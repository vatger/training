<?php

namespace App\Domain\WaitingList\Actions;

use App\Domain\WaitingList\Events\WaitingListPurgedForInactivity;
use App\Domain\WaitingList\Events\WaitingListVerificationRequested;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\WaitingListVerificationRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMonthlyWaitingListVerification
{
    public function __construct(
        private readonly VatgerClientInterface $vatger,
    ) {}

    public function execute(): void
    {
        $yearMonth = now()->format('Y-m');

        if (WaitingListVerificationRun::where('year_month', $yearMonth)->exists()) {
            return;
        }

        DB::transaction(function () use ($yearMonth) {
            $this->purgeUnconfirmed();
            $this->resetAndNotify();

            WaitingListVerificationRun::create([
                'year_month' => $yearMonth,
                'ran_at' => now(),
            ]);
        });
    }

    private function purgeUnconfirmed(): void
    {
        WaitingListEntry::with(['course', 'user'])
            ->where('is_interested', false)
            ->get()
            ->groupBy('user_id')
            ->each(function (Collection $userEntries) {
                $user = $userEntries->first()->user;

                $userEntries->each(function (WaitingListEntry $entry) {
                    event(new WaitingListPurgedForInactivity($entry, $entry->course, $entry->user));
                });

                $this->notifyRemoved($user, $userEntries);

                WaitingListEntry::whereIn('id', $userEntries->pluck('id'))->delete();
            });
    }

    private function resetAndNotify(): void
    {
        WaitingListEntry::query()->update(['is_interested' => false]);

        WaitingListEntry::with(['course', 'user'])
            ->get()
            ->groupBy('user_id')
            ->each(function (Collection $userEntries) {
                $user = $userEntries->first()->user;

                event(new WaitingListVerificationRequested($user, $userEntries));

                $this->notifyPendingConfirmation($user, $userEntries);
            });
    }

    private function notifyRemoved(User $user, Collection $entries): void
    {
        if (! $user->vatsim_id) {
            return;
        }

        $courseNames = $entries->pluck('course.name')->implode(', ');

        try {
            $this->vatger->sendNotification(
                vatsimId: $user->vatsim_id,
                title: 'Removed from Waiting List',
                message: "You've been removed from the waiting list for {$courseNames} because you didn't confirm your interest. You can rejoin from the courses page if you're still interested.",
                sourceName: 'vatger ATD',
                linkUrl: route('courses.index'),
                linkText: 'Training Centre',
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send waiting list removal notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyPendingConfirmation(User $user, Collection $entries): void
    {
        if (! $user->vatsim_id) {
            return;
        }

        $courseNames = $entries->pluck('course.name')->implode(', ');

        try {
            $this->vatger->sendNotification(
                vatsimId: $user->vatsim_id,
                title: 'Confirm Waiting List Interest',
                message: "Your waiting list spot(s) for {$courseNames} require monthly confirmation. Please confirm you're still interested before next month's check, or you'll be removed from the list.",
                sourceName: 'vatger ATD',
                linkUrl: route('courses.index'),
                linkText: 'Training Centre',
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send waiting list verification notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
