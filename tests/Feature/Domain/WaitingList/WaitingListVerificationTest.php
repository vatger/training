<?php

use App\Domain\WaitingList\Actions\ProcessMonthlyWaitingListVerification;
use App\Domain\WaitingList\Events\WaitingListPurgedForInactivity;
use App\Domain\WaitingList\Events\WaitingListVerificationRequested;
use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
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

test('purges entries whose removal_date has passed and fires event', function () {
    Event::fake();

    $entry = verificationEntry(['is_interested' => false, 'removal_date' => now()->subDay()]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $this->assertDatabaseMissing('waiting_list_entries', ['id' => $entry->id]);

    Event::assertDispatched(WaitingListPurgedForInactivity::class, function ($event) use ($entry) {
        return $event->entry->id === $entry->id;
    });
});

test('does not purge an entry whose removal_date is in the future', function () {
    Event::fake();

    $entry = verificationEntry(['is_interested' => false, 'removal_date' => now()->addDay()]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $this->assertDatabaseHas('waiting_list_entries', ['id' => $entry->id]);
    Event::assertNotDispatched(WaitingListPurgedForInactivity::class);
});

test('starts the removal countdown for an entry whose confirmation is older than the grace period', function () {
    Event::fake();
    config(['services.waiting_list.interest_confirmation_days' => 30]);

    $entry = verificationEntry([
        'is_interested' => true,
        'interest_confirmed_at' => now()->subDays(31),
    ]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $entry->refresh();
    expect($entry->is_interested)->toBeFalse();
    expect($entry->removal_date)->not->toBeNull();
    expect($entry->removal_date->isSameDay(now()->addDays(30)))->toBeTrue();

    Event::assertDispatched(WaitingListVerificationRequested::class, function ($event) use ($entry) {
        return $event->entries->pluck('id')->contains($entry->id);
    });
});

test('a brand new entry is not asked to reconfirm before its own grace period has elapsed', function () {
    Event::fake();
    config(['services.waiting_list.interest_confirmation_days' => 30]);

    $entry = verificationEntry([
        'date_added' => now()->subDays(5),
        'is_interested' => true,
        'interest_confirmed_at' => null,
    ]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $entry->refresh();
    expect($entry->is_interested)->toBeTrue();
    expect($entry->removal_date)->toBeNull();
    Event::assertNotDispatched(WaitingListVerificationRequested::class);
});

test('a new entry is asked to reconfirm once its own join date is past the grace period', function () {
    Event::fake();
    config(['services.waiting_list.interest_confirmation_days' => 30]);

    $entry = verificationEntry([
        'date_added' => now()->subDays(31),
        'is_interested' => true,
        'interest_confirmed_at' => null,
    ]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $entry->refresh();
    expect($entry->is_interested)->toBeFalse();
    expect($entry->removal_date)->not->toBeNull();
});

test('an entry not yet due for reconfirmation is left untouched', function () {
    Event::fake();
    config(['services.waiting_list.interest_confirmation_days' => 30]);

    $entry = verificationEntry([
        'is_interested' => true,
        'interest_confirmed_at' => now()->subDays(10),
    ]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $entry->refresh();
    expect($entry->is_interested)->toBeTrue();
    expect($entry->removal_date)->toBeNull();
    Event::assertNotDispatched(WaitingListVerificationRequested::class);
});

test('a user with two entries, one purged and one started, only loses the purged one and gets one notification each', function () {
    Event::fake();
    config(['services.waiting_list.interest_confirmation_days' => 30]);

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
    $purgedCourse = Course::factory()->create(['type' => 'RTG']);
    $startedCourse = Course::factory()->create(['type' => 'FAM']);

    $purgedEntry = verificationEntry(['user' => $user, 'course' => $purgedCourse, 'is_interested' => false, 'removal_date' => now()->subDay()]);
    $startedEntry = verificationEntry(['user' => $user, 'course' => $startedCourse, 'is_interested' => true, 'interest_confirmed_at' => now()->subDays(31)]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $this->assertDatabaseMissing('waiting_list_entries', ['id' => $purgedEntry->id]);
    $this->assertDatabaseHas('waiting_list_entries', ['id' => $startedEntry->id]);

    Event::assertDispatchedTimes(WaitingListVerificationRequested::class, 1);
    Event::assertDispatchedTimes(WaitingListPurgedForInactivity::class, 1);
});

test('running twice back-to-back is idempotent', function () {
    Event::fake();
    config(['services.waiting_list.interest_confirmation_days' => 30]);

    $entry = verificationEntry([
        'is_interested' => true,
        'interest_confirmed_at' => now()->subDays(31),
    ]);

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $entry->refresh();
    $removalDateAfterFirstRun = $entry->removal_date;
    expect($removalDateAfterFirstRun)->not->toBeNull();

    app(ProcessMonthlyWaitingListVerification::class)->execute();

    $entry->refresh();
    expect($entry->removal_date->equalTo($removalDateAfterFirstRun))->toBeTrue();
    Event::assertDispatchedTimes(WaitingListVerificationRequested::class, 1);
});
