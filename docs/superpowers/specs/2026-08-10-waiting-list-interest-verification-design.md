# Waiting List Interest Verification — Design

## Problem

Waiting lists (`waiting_list_entries`) grow long over time, and a portion of people
on them are no longer interested in the training slot they're queued for. There's
currently no mechanism to detect or clear out stale entries.

## Goal

Once a month, require everyone on a waiting list to reconfirm interest. Anyone who
doesn't reconfirm since the previous cycle is automatically removed. The monthly
job must be safe to trigger more than once (e.g. daily schedule) without double-running
its effects for the same month.

## Data model

### `waiting_list_entries` (new columns)

| Column | Type | Default | Notes |
|---|---|---|---|
| `is_interested` | boolean | `true` | Whether the user has confirmed interest for the current cycle. |
| `interest_confirmed_at` | timestamp, nullable | `null` | When the user last confirmed. Audit/UI display only. |

Defaulting to `true` means a newly-created entry is never mistakenly purged for a
cycle it wasn't part of yet — see "New joiners" under Behavior.

### `waiting_list_verification_runs` (new table)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `year_month` | string, unique | e.g. `"2026-08"`. Presence of a row = this month's cycle already ran. |
| `ran_at` | timestamp | |

This mirrors the explicit-state pattern already used by `RosterEntry` (`removal_date`
tracks warning state) rather than a bare settings/config value, giving a queryable
history of past runs.

## Backend

### `app/Domain/WaitingList/Actions/ConfirmWaitingListInterest.php`

```
execute(WaitingListEntry $entry, User $user): array
```

- Verifies `$entry->user_id === $user->id` (authorization belongs in the controller
  via route-model + policy-style check, same as other WaitingList actions).
- Sets `is_interested = true`, `interest_confirmed_at = now()`, saves.
- Fires `WaitingListInterestConfirmed($entry, $entry->course, $user)`.

### `app/Domain/WaitingList/Actions/ProcessMonthlyWaitingListVerification.php`

```
execute(): void
```

1. If `WaitingListVerificationRun::where('year_month', now()->format('Y-m'))->exists()`,
   return immediately (already ran this month).
2. **Purge unconfirmed:** for every `WaitingListEntry` where `is_interested = false`:
   - fire `WaitingListPurgedForInactivity($entry, $entry->course, $entry->user)`
   - delete the entry
   - queue a "you were removed" notification for that user (see Notifications)
3. **Reset + notify:** for every *remaining* entry, set `is_interested = false`
   (the new pending state for this cycle). Group remaining entries by user and:
   - fire `WaitingListVerificationRequested($user, $entries)` once per user
   - send one "please confirm your interest" notification per user, naming all
     affected course(s) if the user has more than one entry
4. Insert the `waiting_list_verification_runs` row for the current `year_month`.

Steps 2–4 run inside a DB transaction so a mid-run failure doesn't leave the run
half-applied while still being recorded as complete (or vice versa).

### Events + Listeners

New events, following the existing `readonly class` event style:
- `WaitingListInterestConfirmed(WaitingListEntry $entry, Course $course, User $user)`
- `WaitingListPurgedForInactivity(WaitingListEntry $entry, Course $course, User $user)`
- `WaitingListVerificationRequested(User $user, Collection $entries)`

Each gets a corresponding `Log*` listener in `app/Listeners/` writing to
`ActivityLog`, matching every other domain event already in the codebase
(`LogWaitingListJoined`, `LogWaitingListLeft`, etc.).

### Console command

`app/Console/Commands/ProcessWaitingListVerification.php`, signature
`waitinglists:verify-interest`. Thin wrapper calling
`ProcessMonthlyWaitingListVerification::execute()`.

Scheduled in `bootstrap/app.php`:

```php
$schedule->command('waitinglists:verify-interest')
    ->dailyAt('06:00')
    ->withoutOverlapping();
```

Runs daily like the other jobs in this schedule; the run-tracking table is what
actually enforces "once per month," so a missed day (deploy downtime, etc.) still
self-heals the next day rather than waiting a full month.

### Notifications

Reuse `VatgerClientInterface::sendNotification()` exactly as `CptNotificationService`
does today — same dedup-by-user approach (`collect(...)->unique('id')`) before
sending, so a user on 2+ waiting lists gets one notification, not one per entry.
Link target: `route('courses.index')`.

Two notification texts:
- **Confirm request:** "Your waiting list spot(s) for <course(s)> require monthly
  confirmation. Please confirm you're still interested before next month's check,
  or you'll be removed from the list."
- **Removed:** "You've been removed from the waiting list for <course> because you
  didn't confirm your interest. You can rejoin from the courses page if you're
  still interested."

### HTTP route

New route in `routes/web.php`, alongside the existing `courses.toggle-waiting-list`:

```php
Route::post('/{course}/waiting-list/confirm-interest', [MentorManagementController::class, 'confirmWaitingListInterest'])
    ->name('confirm-waiting-list-interest');
```

(under `auth`+`verified`, no `mentor` middleware — this is trainee self-service,
same tier as joining/leaving the list). Controller resolves the user's own entry
for that course and calls `ConfirmWaitingListInterest`.

## Behavior / edge cases

- **New joiners:** a `WaitingListEntry` created via `JoinWaitingList` mid-cycle
  defaults `is_interested = true`, so it's never purged by the *next* run (only
  entries the user was actually asked to reconfirm and didn't get purged). It gets
  swept into `is_interested = false` at the following reset step like everyone else.
- **Double-run safety:** enforced entirely by `waiting_list_verification_runs`;
  running the command twice in the same month is a no-op the second time.
- **Multiple entries per user:** each `WaitingListEntry` tracks its own
  `is_interested` state independently (a user can confirm interest in one course's
  queue but let another lapse); notifications are batched per user regardless.

## Frontend

- `Course` type used by `resources/js/pages/training/courses.tsx` gains an optional
  `waiting_list_interest_confirmed?: boolean`, populated from the controller's
  Inertia props (only meaningful when `is_on_waiting_list` is `true`).
- `resources/js/components/courses/waiting-list-button.tsx`: when
  `is_on_waiting_list && waiting_list_interest_confirmed === false`, render a
  "Confirm you're still interested" affordance next to the existing Leave Queue
  button. Follows the file's existing `router.post` + optimistic update + `toast`
  pattern, posting to the new `confirm-waiting-list-interest` route.
- Mentor waiting-list management view (`waiting-lists.manage` page/controller):
  add a small "Confirmed" / "Pending confirmation" badge per row, sourced from the
  same flag, so mentors understand why the list shrinks and aren't surprised by
  removals.

## Testing

New `tests/Feature/Domain/WaitingList/WaitingListVerificationTest.php`, following
the conventions already in `WaitingListActionsTest.php` (`Event::fake()`,
`FakeVatgerClient` bound in `beforeEach`, `assertDatabaseHas`/`assertDatabaseMissing`):

- `ConfirmWaitingListInterest` sets `is_interested = true` and
  `interest_confirmed_at`, fires `WaitingListInterestConfirmed`.
- Running the monthly verification with a mix of confirmed/unconfirmed entries:
  unconfirmed entries are deleted and fire `WaitingListPurgedForInactivity`;
  confirmed entries survive, get reset to `is_interested = false`, and fire
  `WaitingListVerificationRequested`.
- Running the command twice in the same month only applies effects once (second
  run is a no-op — no additional events fired, no additional deletions).
- An entry created after this month's reset is not purged by the same month's
  run, nor incorrectly purged next cycle before it's ever been asked to confirm.
- A user with two entries (one confirmed, one not) only has the unconfirmed one
  removed, and receives exactly one batched notification.

## Out of scope

- Configurable cadence (always monthly, always calendar-month-based for v1).
- Per-course opt-out of the verification requirement.
- Any change to the existing `JoinWaitingList`/`LeaveWaitingList` validation rules.
