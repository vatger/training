# Manual Testing — Waiting List Interest Verification

How to click through the monthly waiting-list interest verification feature
(see `docs/superpowers/specs/2026-08-10-waiting-list-interest-verification-design.md`
for the design) on a machine without PHP/Composer/Node/local MySQL installed
— everything (backend and frontend tooling) runs inside the dev container.

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

`npm run dev` needs `php` on its `PATH` (the Wayfinder Vite plugin shells
out to `php artisan wayfinder:generate` on every start and on every route
change), so it runs inside the same container rather than on the host —
`Dockerfile.dev` includes Node/npm alongside PHP for exactly this reason.
`node_modules` lives in its own Docker volume (not the bind mount), since a
Linux container and a non-Linux host can't safely share one `node_modules`
(native packages like `esbuild` ship platform-specific binaries).

```bash
docker compose exec app npm install
docker compose exec app npm run dev -- --host 0.0.0.0
```

Leave this running in its own terminal. The first start is slow (Wayfinder's
route codegen can take ~30-45s cold in the container); subsequent starts and
route-file writes are much faster. Once you see `VITE ... ready in ...ms`,
Vite is reachable at http://localhost:5173 (mapped through in
`docker-compose.yml`) and serves assets with HMR to the app at
http://localhost:8000.

If you'd rather not keep a dev server running, `docker compose exec app npm
run build` produces a static production build instead (same PHP dependency,
same container) — reload the page after each change instead of getting HMR.

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
"Confirm Waiting List Interest" fake notification per user. The purge step
is additionally skipped if the previous run was less than ~25 days ago
(protects against hard-deleting everyone if two runs land close together
across a month boundary) — reset+notify still happens either way. A second
run in the same month is a no-op — check with:

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

The SQLite database file and `vendor/` persist on the host via the bind
mount; `node_modules/` persists in its own Docker volume (see step 2). Either
way, `docker compose up -d` again picks up where you left off (migrations
only re-run what's pending, and `npm install` is a no-op if nothing
changed).
