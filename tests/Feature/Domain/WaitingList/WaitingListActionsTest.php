<?php

use App\Domain\WaitingList\Actions\JoinWaitingList;
use App\Domain\WaitingList\Actions\LeaveWaitingList;
use App\Domain\WaitingList\Events\WaitingListJoined;
use App\Domain\WaitingList\Events\WaitingListLeft;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Integrations\Vatger\FakeVatgerClient;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\WaitingListRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
    $this->app->bind(VatgerClientInterface::class, FakeVatgerClient::class);
    Http::fake(); // Prevent real HTTP calls from CourseValidationService
    Cache::flush();
});

// Swap to a fresh Http factory with a specific roster response.
// Http::fake() in beforeEach adds a catch-all with a numeric key that fires
// before any URL-pattern stubs added in the test, so a full swap is needed.
function fakeRosterWith(array $vatsimIds): void
{
    Http::swap(new HttpFactory);
    Http::fake(['*' => Http::response(['data' => ['controllers' => $vatsimIds]], 200)]);
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
