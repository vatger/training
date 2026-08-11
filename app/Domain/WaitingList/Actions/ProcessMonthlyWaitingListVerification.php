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
    /**
     * Minimum number of real days that must have elapsed since the previous run
     * before unconfirmed entries are purged. Deliberately less than a full month
     * so a run landing a few days early/late still purges normally, but two runs
     * landing implausibly close together (e.g. a first run late in a month
     * followed by one on the 1st, or after scheduler downtime across a month
     * boundary) skip the purge so people actually get a month to reconfirm.
     */
    private const MIN_DAYS_BETWEEN_PURGES = 25;

    public function __construct(
        private readonly VatgerClientInterface $vatger,
    ) {}

    public function execute(): void
    {
        $yearMonth = now()->format('Y-m');

        if (WaitingListVerificationRun::where('year_month', $yearMonth)->exists()) {
            return;
        }

        $lastRun = WaitingListVerificationRun::orderByDesc('ran_at')->first();
        $shouldPurge = ! $lastRun || $lastRun->ran_at->diffInDays(now()) >= self::MIN_DAYS_BETWEEN_PURGES;

        $removalNotifications = collect();
        $pendingNotifications = collect();

        DB::transaction(function () use ($yearMonth, $shouldPurge, &$removalNotifications, &$pendingNotifications) {
            if ($shouldPurge) {
                $removalNotifications = $this->purgeUnconfirmed();
            }

            $pendingNotifications = $this->resetAndNotify();

            WaitingListVerificationRun::create([
                'year_month' => $yearMonth,
                'ran_at' => now(),
            ]);
        });

        // Notifications are sent only after the transaction commits: they are
        // real HTTP calls and must not hold DB write locks open, nor be sent
        // for a transaction that later rolls back.
        $removalNotifications->each(fn (array $n) => $this->notifyRemoved($n['user'], $n['courseNames']));
        $pendingNotifications->each(fn (array $n) => $this->notifyPendingConfirmation($n['user'], $n['courseNames']));
    }

    /**
     * @return Collection<int, array{user: User, courseNames: string}>
     */
    private function purgeUnconfirmed(): Collection
    {
        $notifications = collect();

        WaitingListEntry::with(['course', 'user'])
            ->where('is_interested', false)
            ->get()
            ->groupBy('user_id')
            ->each(function (Collection $userEntries) use (&$notifications) {
                $user = $userEntries->first()->user;

                $userEntries->each(function (WaitingListEntry $entry) {
                    event(new WaitingListPurgedForInactivity($entry, $entry->course, $entry->user));
                });

                $notifications->push([
                    'user' => $user,
                    'courseNames' => $userEntries->pluck('course.name')->implode(', '),
                ]);

                WaitingListEntry::whereIn('id', $userEntries->pluck('id'))->delete();
            });

        return $notifications;
    }

    /**
     * @return Collection<int, array{user: User, courseNames: string}>
     */
    private function resetAndNotify(): Collection
    {
        WaitingListEntry::where('is_interested', true)->update(['is_interested' => false]);

        $notifications = collect();

        WaitingListEntry::with(['course', 'user'])
            ->get()
            ->groupBy('user_id')
            ->each(function (Collection $userEntries) use (&$notifications) {
                $user = $userEntries->first()->user;

                event(new WaitingListVerificationRequested($user, $userEntries));

                $notifications->push([
                    'user' => $user,
                    'courseNames' => $userEntries->pluck('course.name')->implode(', '),
                ]);
            });

        return $notifications;
    }

    private function notifyRemoved(User $user, string $courseNames): void
    {
        if (! $user->vatsim_id) {
            return;
        }

        try {
            $result = $this->vatger->sendNotification(
                vatsimId: $user->vatsim_id,
                title: 'Removed from Waiting List',
                message: "You've been removed from the waiting list for {$courseNames} because you didn't confirm your interest. You can rejoin from the courses page if you're still interested.",
                sourceName: 'vatger ATD',
                linkUrl: route('courses.index'),
                linkText: 'Training Centre',
            );

            if (! ($result['success'] ?? false)) {
                Log::warning('Failed to send waiting list removal notification', [
                    'user_id' => $user->id,
                    'course_names' => $courseNames,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send waiting list removal notification', [
                'user_id' => $user->id,
                'course_names' => $courseNames,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyPendingConfirmation(User $user, string $courseNames): void
    {
        if (! $user->vatsim_id) {
            return;
        }

        try {
            $result = $this->vatger->sendNotification(
                vatsimId: $user->vatsim_id,
                title: 'Confirm Waiting List Interest',
                message: "Your waiting list spot(s) for {$courseNames} require monthly confirmation. Please confirm you're still interested before next month's check, or you'll be removed from the list.",
                sourceName: 'vatger ATD',
                linkUrl: route('courses.index'),
                linkText: 'Training Centre',
            );

            if (! ($result['success'] ?? false)) {
                Log::warning('Failed to send waiting list verification notification', [
                    'user_id' => $user->id,
                    'course_names' => $courseNames,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send waiting list verification notification', [
                'user_id' => $user->id,
                'course_names' => $courseNames,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
