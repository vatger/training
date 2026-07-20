<?php

use App\Domain\Roster\Actions\CheckUserRosterStatus;
use App\Domain\Roster\Actions\RemoveUserFromRoster;
use App\Domain\Roster\Events\RosterRemovalWarningIssued;
use App\Domain\Roster\Events\UserRemovedFromRoster;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\Course;
use App\Models\RosterEntry;
use App\Models\User;
use App\Models\WaitingListEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
    $this->app->bind(VatgerClientInterface::class, FakeVatgerClient::class);
    Http::fake();
    Cache::flush();
});

// ─── CheckUserRosterStatus ────────────────────────────────────────────────────

test('CheckUserRosterStatus creates entry and sets last_session for active user', function () {
    // FakeVatEudClient default returns 10 days ago — well within 330-day threshold
    $vatsimId = 1234567;

    app(CheckUserRosterStatus::class)->execute($vatsimId);

    $entry = RosterEntry::where('user_id', $vatsimId)->first();

    expect($entry)->not->toBeNull();
    expect($entry->last_session)->not->toBeNull();
    expect($entry->last_session->diffInDays(now()))->toBeLessThan(15);
    expect($entry->removal_date)->toBeNull();
});

test('CheckUserRosterStatus sends warning and sets removal_date for user inactive 340 days', function () {
    Event::fake();

    $vatsimId = 7654321;

    $this->app->bind(VatEudClientInterface::class, fn () => new class extends FakeVatEudClient
    {
        public function getLastGermanSession(int $vatsimId): ?Carbon
        {
            return Carbon::now()->subDays(340);
        }
    });

    app(CheckUserRosterStatus::class)->execute($vatsimId);

    $entry = RosterEntry::where('user_id', $vatsimId)->first();

    expect($entry)->not->toBeNull();
    expect($entry->removal_date)->not->toBeNull();
    expect($entry->removal_date->toDateString())->toBe(now()->addDays(35)->toDateString());

    Event::assertDispatched(RosterRemovalWarningIssued::class, function ($event) use ($vatsimId) {
        return $event->vatsimId === $vatsimId;
    });
});

test('CheckUserRosterStatus removes user when inactive 366 days and removal_date has passed', function () {
    Event::fake();

    $vatsimId = 9876543;

    RosterEntry::create([
        'user_id' => $vatsimId,
        'last_session' => now()->subDays(366),
        'removal_date' => now()->subDay(),
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => new class extends FakeVatEudClient
    {
        public function getLastGermanSession(int $vatsimId): ?Carbon
        {
            return Carbon::now()->subDays(366);
        }
    });

    app(CheckUserRosterStatus::class)->execute($vatsimId);

    $this->assertDatabaseMissing('roster_entries', ['user_id' => $vatsimId]);

    Event::assertDispatched(UserRemovedFromRoster::class, function ($event) use ($vatsimId) {
        return $event->vatsimId === $vatsimId;
    });
});

test('CheckUserRosterStatus clears removal_date when user becomes active again', function () {
    $vatsimId = 1122334;

    RosterEntry::create([
        'user_id' => $vatsimId,
        'last_session' => now()->subDays(340),
        'removal_date' => now()->addDays(10),
    ]);

    // Default fake returns 10 days ago — user is now active again
    app(CheckUserRosterStatus::class)->execute($vatsimId);

    $entry = RosterEntry::where('user_id', $vatsimId)->first();

    expect($entry)->not->toBeNull();
    expect($entry->removal_date)->toBeNull();
});

// ─── RemoveUserFromRoster ─────────────────────────────────────────────────────

test('RemoveUserFromRoster success: waiting list entries deleted and event fired', function () {
    Event::fake();

    $user = User::factory()->create();
    $course = Course::factory()->create(['type' => 'RTG']);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ]);

    app(RemoveUserFromRoster::class)->execute($user->vatsim_id);

    $this->assertDatabaseMissing('waiting_list_entries', ['user_id' => $user->id]);

    Event::assertDispatched(UserRemovedFromRoster::class, function ($event) use ($user) {
        return $event->vatsimId === $user->vatsim_id;
    });
});

test('RemoveUserFromRoster failure: entries not deleted and event not fired when API call fails', function () {
    Event::fake();

    $user = User::factory()->create();
    $course = Course::factory()->create(['type' => 'RTG']);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ]);

    $this->app->bind(VatEudClientInterface::class, fn () => new class extends FakeVatEudClient
    {
        public function removeRosterAndEndorsements(int $vatsimId): bool
        {
            return false;
        }
    });

    app(RemoveUserFromRoster::class)->execute($user->vatsim_id);

    $this->assertDatabaseHas('waiting_list_entries', ['user_id' => $user->id]);

    Event::assertNotDispatched(UserRemovedFromRoster::class);
});
