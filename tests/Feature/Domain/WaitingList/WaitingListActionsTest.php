<?php

use App\Domain\WaitingList\Actions\ConfirmWaitingListInterest;
use App\Domain\WaitingList\Actions\JoinWaitingList;
use App\Domain\WaitingList\Actions\LeaveWaitingList;
use App\Domain\WaitingList\Events\WaitingListInterestConfirmed;
use App\Domain\WaitingList\Events\WaitingListJoined;
use App\Domain\WaitingList\Events\WaitingListLeft;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\WaitingListRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatgerClientInterface::class, FakeVatgerClient::class);
    Cache::flush();
});

// Bind a mocked VatEudClientInterface returning a specific roster.
function fakeRosterWith(array $vatsimIds): void
{
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getRoster')->andReturn($vatsimIds);
    app()->instance(VatEudClientInterface::class, $client);
    Cache::flush();
}

// ─── JoinWaitingList ──────────────────────────────────────────────────────────

test('JoinWaitingList success: entry created and event fired', function () {
    Event::fake();

    $user = User::factory()->create(['rating' => 2, 'subdivision' => 'GER', 'last_rating_change' => now()->subDays(100)]);
    fakeRosterWith([$user->vatsim_id]);

    $course = Course::factory()->create(['type' => 'RTG', 'min_rating' => 2, 'max_rating' => 3]);

    [$success, $message] = app(JoinWaitingList::class)->execute($course, $user);

    expect($success)->toBeTrue();
    expect($message)->toBe('Successfully joined waiting list.');

    $this->assertDatabaseHas('waiting_list_entries', [
        'user_id' => $user->id,
        'course_id' => $course->id,
        'activity' => 0,
    ]);

    Event::assertDispatched(WaitingListJoined::class, function ($event) use ($user, $course) {
        return $event->user->id === $user->id && $event->course->id === $course->id;
    });
});

test('JoinWaitingList fails if user is already on waiting list for that course', function () {
    Event::fake();

    $user = User::factory()->create(['rating' => 2, 'subdivision' => 'GER', 'last_rating_change' => now()->subDays(100)]);
    fakeRosterWith([$user->vatsim_id]);

    $course = Course::factory()->create(['type' => 'RTG', 'min_rating' => 2, 'max_rating' => 3]);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ]);

    [$success, $message] = app(JoinWaitingList::class)->execute($course, $user);

    expect($success)->toBeFalse();
    expect($message)->toBe('You are already on the waiting list for this course.');
});

test('JoinWaitingList fails if user is already on waiting list for a different RTG course', function () {
    Event::fake();

    $user = User::factory()->create(['rating' => 2, 'subdivision' => 'GER', 'last_rating_change' => now()->subDays(100)]);
    fakeRosterWith([$user->vatsim_id]);

    $courseA = Course::factory()->create(['type' => 'RTG', 'min_rating' => 2, 'max_rating' => 3]);
    $courseB = Course::factory()->create(['type' => 'RTG', 'min_rating' => 2, 'max_rating' => 3]);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $courseB->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ]);

    [$success, $message] = app(JoinWaitingList::class)->execute($courseA, $user);

    expect($success)->toBeFalse();
    expect($message)->toBe('You are already on the waiting list for a rating course. You can only join one rating course at a time.');
});

test('JoinWaitingList fails if user is already on an EDMT waiting list and tries to join another EDMT course', function () {
    Event::fake();

    $user = User::factory()->create(['rating' => 2, 'subdivision' => 'GER', 'last_rating_change' => now()->subDays(100)]);
    fakeRosterWith([$user->vatsim_id]);

    $courseA = Course::factory()->create(['type' => 'EDMT', 'min_rating' => 2, 'max_rating' => 3]);
    $courseB = Course::factory()->create(['type' => 'EDMT', 'min_rating' => 2, 'max_rating' => 3]);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $courseB->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ]);

    [$success, $message] = app(JoinWaitingList::class)->execute($courseA, $user);

    expect($success)->toBeFalse();
    expect($message)->toBe('You are already on the waiting list for an endorsement course. You can only join one endorsement course at a time.');
});

test('JoinWaitingList fails if user is already on a FAM waiting list and tries to join another FAM course', function () {
    Event::fake();

    $user = User::factory()->create(['rating' => 2, 'subdivision' => 'GER', 'last_rating_change' => now()->subDays(100)]);
    fakeRosterWith([$user->vatsim_id]);

    $courseA = Course::factory()->create(['type' => 'FAM', 'min_rating' => 2, 'max_rating' => 3]);
    $courseB = Course::factory()->create(['type' => 'FAM', 'min_rating' => 2, 'max_rating' => 3]);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $courseB->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ]);

    [$success, $message] = app(JoinWaitingList::class)->execute($courseA, $user);

    expect($success)->toBeFalse();
    expect($message)->toBe('You are already on the waiting list for a familiarisation course. You can only join one familiarisation course at a time.');
});

test('JoinWaitingList succeeds if user is already on an EDMT waiting list and joins a FAM course', function () {
    Event::fake();

    $user = User::factory()->create(['rating' => 2, 'subdivision' => 'GER', 'last_rating_change' => now()->subDays(100)]);
    fakeRosterWith([$user->vatsim_id]);

    $edmtCourse = Course::factory()->create(['type' => 'EDMT', 'min_rating' => 2, 'max_rating' => 3]);
    $famCourse = Course::factory()->create(['type' => 'FAM', 'min_rating' => 2, 'max_rating' => 3]);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $edmtCourse->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ]);

    [$success, $message] = app(JoinWaitingList::class)->execute($famCourse, $user);

    expect($success)->toBeTrue();
    expect($message)->toBe('Successfully joined waiting list.');
    expect(WaitingListEntry::where('user_id', $user->id)->where('course_id', $famCourse->id)->exists())->toBeTrue();
});

test('JoinWaitingList succeeds if user is already on a FAM waiting list and joins an EDMT course', function () {
    Event::fake();

    $user = User::factory()->create(['rating' => 2, 'subdivision' => 'GER', 'last_rating_change' => now()->subDays(100)]);
    fakeRosterWith([$user->vatsim_id]);

    $famCourse = Course::factory()->create(['type' => 'FAM', 'min_rating' => 2, 'max_rating' => 3]);
    $edmtCourse = Course::factory()->create(['type' => 'EDMT', 'min_rating' => 2, 'max_rating' => 3]);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $famCourse->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ]);

    [$success, $message] = app(JoinWaitingList::class)->execute($edmtCourse, $user);

    expect($success)->toBeTrue();
    expect($message)->toBe('Successfully joined waiting list.');
    expect(WaitingListEntry::where('user_id', $user->id)->where('course_id', $edmtCourse->id)->exists())->toBeTrue();
});

test('JoinWaitingList fails if user is restricted from joining that course type', function () {
    Event::fake();

    $user = User::factory()->create(['rating' => 2, 'subdivision' => 'GER']);
    fakeRosterWith([$user->vatsim_id]);

    $course = Course::factory()->create(['type' => 'RTG', 'min_rating' => 2, 'max_rating' => 3]);

    WaitingListRestriction::create([
        'user_id' => $user->id,
        'type' => 'RTG',
        'expires_at' => now()->addDays(30),
    ]);

    [$success, $message] = app(JoinWaitingList::class)->execute($course, $user);

    expect($success)->toBeFalse();
    expect($message)->toBe('You are currently restricted from joining this type of waiting list.');
});

// ─── LeaveWaitingList ─────────────────────────────────────────────────────────

test('LeaveWaitingList success: entry deleted and event fired', function () {
    Event::fake();

    $user = User::factory()->create();
    $course = Course::factory()->create(['type' => 'RTG']);

    $entry = WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ]);

    [$success, $message] = app(LeaveWaitingList::class)->execute($course, $user);

    expect($success)->toBeTrue();
    expect($message)->toBe('Successfully left waiting list.');

    $this->assertDatabaseMissing('waiting_list_entries', ['id' => $entry->id]);

    Event::assertDispatched(WaitingListLeft::class, function ($event) use ($user, $course) {
        return $event->user->id === $user->id && $event->course->id === $course->id;
    });
});

test('LeaveWaitingList fails if user is not on the waiting list', function () {
    Event::fake();

    $user = User::factory()->create();
    $course = Course::factory()->create(['type' => 'RTG']);

    [$success, $message] = app(LeaveWaitingList::class)->execute($course, $user);

    expect($success)->toBeFalse();
    expect($message)->toBe('You are not on the waiting list for this course.');
});

// ─── ConfirmWaitingListInterest ────────────────────────────────────────────────

test('ConfirmWaitingListInterest sets is_interested, timestamp, clears removal_date, fires event', function () {
    Event::fake();

    $user = User::factory()->create();
    $course = Course::factory()->create(['type' => 'RTG']);

    $entry = WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
        'is_interested' => false,
        'removal_date' => now()->addDays(5),
    ]);

    app(ConfirmWaitingListInterest::class)->execute($entry, $user);

    $entry->refresh();

    expect($entry->is_interested)->toBeTrue();
    expect($entry->interest_confirmed_at)->not->toBeNull();
    expect($entry->removal_date)->toBeNull();

    Event::assertDispatched(WaitingListInterestConfirmed::class, function ($event) use ($user, $course, $entry) {
        return $event->user->id === $user->id
            && $event->course->id === $course->id
            && $event->entry->id === $entry->id;
    });
});
