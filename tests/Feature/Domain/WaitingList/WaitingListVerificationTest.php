<?php

use App\Domain\WaitingList\Actions\ProcessMonthlyWaitingListVerification;
use App\Domain\WaitingList\Events\WaitingListPurgedForInactivity;
use App\Domain\WaitingList\Events\WaitingListVerificationRequested;
use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\WaitingListVerificationRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatgerClientInterface::class, FakeVatgerClient::class);
});

function verificationEntry(array $overrides = []): WaitingListEntry
{
    $user = $overrides['user'] ?? User::factory()->create();
    $course = $overrides['course'] ?? Course::factory()->create(['type' => 'RTG']);

    return WaitingListEntry::create(array_merge([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ], array_diff_key($overrides, ['user' => true, 'course' => true])));
}

test('purges entries with is_interested false and fires event', function () {
    Event::fake();

    $entry = verificationEntry(['is_interested' => false]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $this->assertDatabaseMissing('waiting_list_entries', ['id' => $entry->id]);

    Event::assertDispatched(WaitingListPurgedForInactivity::class, function ($event) use ($entry) {
        return $event->entry->id === $entry->id;
    });
});

test('keeps entries with is_interested true, resets them to false, and requests reconfirmation', function () {
    Event::fake();

    $entry = verificationEntry(['is_interested' => true]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $entry->refresh();

    expect($entry->is_interested)->toBeFalse();

    Event::assertDispatched(WaitingListVerificationRequested::class, function ($event) use ($entry) {
        return $event->entries->pluck('id')->contains($entry->id);
    });
});

test('records a run for the current month', function () {
    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $this->assertDatabaseHas('waiting_list_verification_runs', [
        'year_month' => now()->format('Y-m'),
    ]);
});

test('running twice in the same month only applies effects once', function () {
    Event::fake();

    $unconfirmed = verificationEntry(['is_interested' => false]);
    $confirmed = verificationEntry(['is_interested' => true]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $this->assertDatabaseMissing('waiting_list_entries', ['id' => $unconfirmed->id]);
    $confirmed->refresh();
    expect($confirmed->is_interested)->toBeFalse();

    // Second run in the same month: no further deletions, no further resets.
    $confirmed->update(['is_interested' => true]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $confirmed->refresh();
    expect($confirmed->is_interested)->toBeTrue();

    expect(WaitingListVerificationRun::where('year_month', now()->format('Y-m'))->count())->toBe(1);
});

test('an entry created after this cycle reset is not purged by the same cycle', function () {
    Event::fake();

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $lateJoiner = verificationEntry();
    expect($lateJoiner->is_interested)->toBeTrue();

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $this->assertDatabaseHas('waiting_list_entries', ['id' => $lateJoiner->id]);
});

test('skips the purge when the previous run happened only a few days ago', function () {
    Event::fake();

    // A previous run recorded under a different year_month, but which actually
    // ran only two days ago (e.g. first run late in a month, next run on the 1st).
    WaitingListVerificationRun::create([
        'year_month' => now()->subMonthNoOverflow()->format('Y-m'),
        'ran_at' => now()->subDays(2),
    ]);

    $unconfirmed = verificationEntry(['is_interested' => false]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    // Purge skipped: the user has not had a real month to reconfirm.
    $this->assertDatabaseHas('waiting_list_entries', ['id' => $unconfirmed->id]);
    Event::assertNotDispatched(WaitingListPurgedForInactivity::class);

    // But the run itself still happens: reset + reconfirmation request.
    $this->assertDatabaseHas('waiting_list_verification_runs', ['year_month' => now()->format('Y-m')]);
    expect($unconfirmed->fresh()->is_interested)->toBeFalse();
    Event::assertDispatched(WaitingListVerificationRequested::class, function ($event) use ($unconfirmed) {
        return $event->entries->pluck('id')->contains($unconfirmed->id);
    });
});

test('purges normally when the previous run is old enough', function () {
    Event::fake();

    WaitingListVerificationRun::create([
        'year_month' => now()->subMonthNoOverflow()->format('Y-m'),
        'ran_at' => now()->subDays(30),
    ]);

    $unconfirmed = verificationEntry(['is_interested' => false]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $this->assertDatabaseMissing('waiting_list_entries', ['id' => $unconfirmed->id]);
    Event::assertDispatched(WaitingListPurgedForInactivity::class);
});

test('a user with two entries, one confirmed and one not, only loses the unconfirmed one and gets one notification', function () {
    Event::fake();

    // Notifications are grouped per user, not per entry: exactly one "removed"
    // notification (covering the purged course) and exactly one "confirm"
    // notification (covering the surviving course) — never one per entry.
    $vatgerMock = Mockery::mock(VatgerClientInterface::class);
    $vatgerMock->shouldReceive('sendNotification')
        ->once()
        ->withArgs(fn (...$args) => $args[1] === 'Removed from Waiting List')
        ->andReturn(['success' => true]);
    $vatgerMock->shouldReceive('sendNotification')
        ->once()
        ->withArgs(fn (...$args) => $args[1] === 'Confirm Waiting List Interest')
        ->andReturn(['success' => true]);
    app()->instance(VatgerClientInterface::class, $vatgerMock);

    $user = User::factory()->create(['vatsim_id' => 1234567]);
    $confirmedCourse = Course::factory()->create(['type' => 'RTG']);
    $unconfirmedCourse = Course::factory()->create(['type' => 'FAM']);

    $confirmedEntry = verificationEntry(['user' => $user, 'course' => $confirmedCourse, 'is_interested' => true]);
    $unconfirmedEntry = verificationEntry(['user' => $user, 'course' => $unconfirmedCourse, 'is_interested' => false]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $this->assertDatabaseMissing('waiting_list_entries', ['id' => $unconfirmedEntry->id]);
    $this->assertDatabaseHas('waiting_list_entries', ['id' => $confirmedEntry->id]);

    Event::assertDispatchedTimes(WaitingListVerificationRequested::class, 1);
});
