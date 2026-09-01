<?php

namespace App\Domain\WaitingList\Actions;

use App\Domain\WaitingList\Events\WaitingListPurgedForInactivity;
use App\Domain\WaitingList\Events\WaitingListVerificationRequested;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessMonthlyWaitingListVerification
{
    public function __construct(
        private readonly VatgerClientInterface $vatger,
    ) {}

    public function execute(): void
    {
        $this->purgeExpired()->each(fn (array $n) => $this->notifyRemoved($n['user'], $n['courseNames']));
        $this->startConfirmationWindow()->each(fn (array $n) => $this->notifyPendingConfirmation($n['user'], $n['courseNames']));
    }

    /**
     * @return Collection<int, array{user: User, courseNames: string}>
     */
    private function purgeExpired(): Collection
    {
        $notifications = collect();

        WaitingListEntry::with(['course', 'user'])
            ->whereNotNull('removal_date')
            ->where('removal_date', '<=', now())
            ->get()
            ->groupBy('user_id')
            ->each(function (Collection $userEntries) use (&$notifications) {
                $user = $userEntries->first()->user;

                $userEntries->each(fn (WaitingListEntry $entry) => event(
                    new WaitingListPurgedForInactivity($entry, $entry->course, $entry->user)
                ));

                $notifications->push([
                    'user' => $user,
                    'courseNames' => $userEntries->pluck('course.name')->implode(', '),
                ]);

                WaitingListEntry::whereIn('id', $userEntries->pluck('id'))->delete();
            });

        return $notifications;
    }

    /**
     * Starts the removal countdown for entries whose interest hasn't been
     * (re)confirmed within the configured grace period. A never-confirmed
     * entry's window starts at date_added, not at creation of this job run,
     * so a brand new entry always gets a full grace period before being
     * asked to reconfirm.
     *
     * @return Collection<int, array{user: User, courseNames: string}>
     */
    private function startConfirmationWindow(): Collection
    {
        $graceDays = (int) config('services.waiting_list.interest_confirmation_days');
        $cutoff = now()->subDays($graceDays);

        $due = WaitingListEntry::with(['course', 'user'])
            ->whereNull('removal_date')
            ->where('is_interested', true)
            ->where(function ($query) use ($cutoff) {
                $query->where(function ($q) use ($cutoff) {
                    $q->whereNotNull('interest_confirmed_at')->where('interest_confirmed_at', '<=', $cutoff);
                })->orWhere(function ($q) use ($cutoff) {
                    $q->whereNull('interest_confirmed_at')->where('date_added', '<=', $cutoff);
                });
            })
            ->get();

        $due->each(fn (WaitingListEntry $entry) => $entry->update([
            'is_interested' => false,
            'removal_date' => now()->addDays($graceDays),
        ]));

        $notifications = collect();

        $due->groupBy('user_id')->each(function (Collection $userEntries) use (&$notifications) {
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
                message: "Your waiting list spot(s) for {$courseNames} require confirmation. Please confirm you're still interested, or you'll be removed from the list.",
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
