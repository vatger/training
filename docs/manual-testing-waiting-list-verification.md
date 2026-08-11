# Manual Testing — Waiting List Interest Verification

How to click through the monthly waiting-list interest verification feature
(see `docs/superpowers/specs/2026-08-10-waiting-list-interest-verification-design.md`
for the design) on a machine without PHP/Composer/local MySQL installed.

## 1. Start the app

```bash
docker compose up -d --build
```

This builds `Dockerfile.dev` (PHP 8.4 CLI + Composer), installs dependencies,
generates an `APP_KEY` if missing, creates and migrates a SQLite database at
`database/database.sqlite`, and serves the app at http://localhost:8000.

Because `APP_ENV=local` (the `.env.example` default), `AppServiceProvider`
automatically binds the `Fake*Client` implementations for VATEUD/VATGER/Moodle
(see `app/Providers/AppServiceProvider.php:34-48`) — no real API credentials
are needed, and `FakeVatgerClient::sendNotification()` just logs what it would
have sent (check `storage/logs/laravel.log`, or `docker compose logs app`, for
`[FakeVatger] sendNotification` lines).

## 2. Start the frontend

Node/npm run fine on the host — no container needed for this part:

```bash
npm install
npm run dev
```

Leave this running; Vite serves assets with HMR to the app at
http://localhost:8000.

## 3. Log in

The dev-only VATSIM Connect sandbox login lives at `/auth/vatsim/sandbox`
(see `app/Support/SandboxAuth.php`). It needs, in `.env`:

```
VATSIM_SANDBOX_CLIENT_ID=<from vatsim.dev sandbox>
VATSIM_SANDBOX_CLIENT_SECRET=<from vatsim.dev sandbox>
VATSIM_SANDBOX_ALLOWED_HOSTS=localhost,127.0.0.1
```

Get sandbox credentials from the "VATSIM Connect Demo" OAuth organization at
https://vatsim.dev/services/connect/sandbox/. Without these, you can still
verify the console-only parts of this feature (step 5) but not the UI parts.

If you don't want to set up sandbox OAuth, an alternative is seeding a user
directly and using Laravel's session-based test login helpers via
`php artisan tinker` — not covered here since it bypasses the real auth flow
this feature's UI depends on (`request()->user()` in the controllers).

## 4. Set up a waiting-list entry to test with

Once logged in as a VATSIM-linked user, join a course's waiting list from
`/courses` (the existing "Join Queue" button). Then, to see both UI states
without waiting a month, flip the entry's confirmation flag directly:

```bash
docker compose exec app php artisan tinker
>>> $entry = \App\Models\WaitingListEntry::latest()->first();
>>> $entry->update(['is_interested' => false]);
```

Reload `/courses` — the "Confirm you're still interested" button
(`resources/js/components/courses/waiting-list-button.tsx`) should now
appear next to "Leave Queue". Click it; the button should disappear and a
"Thanks for confirming your interest!" toast should show. Confirm in tinker:

```bash
>>> $entry->refresh()->is_interested; // true
>>> $entry->interest_confirmed_at;    // a recent timestamp
```

## 5. Run the monthly verification cycle on demand

Instead of waiting for the daily 06:00 schedule (`bootstrap/app.php`), trigger
it directly:

```bash
docker compose exec app php artisan waitinglists:verify-interest
```

First run of a calendar month: purges any entry still `is_interested=false`
(fires a "Removed from Waiting List" fake notification — check the logs),
then resets every remaining entry to `is_interested=false` and fires a
"Confirm Waiting List Interest" fake notification per user. A second run in
the same month is a no-op — check with:

```bash
docker compose exec app php artisan tinker
>>> \App\Models\WaitingListVerificationRun::all(['year_month', 'ran_at']);
```

To re-test the cycle without waiting for next month, delete the run record:

```bash
>>> \App\Models\WaitingListVerificationRun::truncate();
```

## 6. Check the mentor view

Log in as (or promote your test user to) a mentor for the course, visit
`/waiting-lists/manage`, select the course, and confirm each waiting-list
entry shows a "Confirmed" or "Pending confirmation" badge
(`resources/js/pages/training/mentor-waiting-lists.tsx`) matching the
`is_interested` value you set in step 4/5.

## Stopping

```bash
docker compose down
```

The SQLite database file and `vendor/`/`node_modules/` persist on the host
via the bind mount, so `docker compose up -d` again picks up where you left
off (migrations only re-run what's pending).
