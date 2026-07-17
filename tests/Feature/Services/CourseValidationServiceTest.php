<?php

use App\Models\Course;
use App\Models\Familiarisation;
use App\Models\FamiliarisationSector;
use App\Models\User;
use App\Models\WaitingListRestriction;
use App\Services\CourseValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Event::fake() suppresses Eloquent model events, preventing the call to
    // App\Services\ActivityLogger (absent on this branch) via LogsActivity trait.
    Event::fake();
    Cache::flush();
});

function fakeRosterWithIds(array $vatsimIds): void
{
    Http::swap(new HttpFactory());
    Http::fake(['*' => Http::response(['data' => ['controllers' => $vatsimIds]], 200)]);
    Cache::flush();
}

function makeService(): CourseValidationService
{
    return app(CourseValidationService::class);
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function gerUserOnRoster(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['subdivision' => 'GER', 'rating' => 3], $attrs));
    fakeRosterWithIds([$user->vatsim_id]);
    return $user;
}

function gerUserOffRoster(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['subdivision' => 'GER', 'rating' => 3], $attrs));
    fakeRosterWithIds([9999999]); // someone else on roster
    return $user;
}

function visitorOnRoster(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['subdivision' => 'USA', 'rating' => 3], $attrs));
    fakeRosterWithIds([$user->vatsim_id]);
    return $user;
}

function foreignOffRoster(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['subdivision' => 'USA', 'rating' => 3], $attrs));
    fakeRosterWithIds([9999999]);
    return $user;
}

// ─── GER on roster ────────────────────────────────────────────────────────────

test('ger user on roster cannot join rst course', function () {
    $user = gerUserOnRoster();
    $course = Course::factory()->create(['type' => 'RST', 'min_rating' => 1, 'max_rating' => 7]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You are already on the roster and cannot join roster reentry courses.');
});

test('ger user on roster cannot join gst course', function () {
    $user = gerUserOnRoster();
    $course = Course::factory()->create(['type' => 'GST', 'min_rating' => 1, 'max_rating' => 7]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You are not allowed to join visitor courses.');
});

test('ger user on roster can join rtg course with correct rating', function () {
    $user = gerUserOnRoster(['rating' => 3]);
    $course = Course::factory()->create(['type' => 'RTG', 'min_rating' => 2, 'max_rating' => 4, 'position' => 'GND']);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue()
        ->and($reason)->toBe('');
});

// ─── GER off roster ───────────────────────────────────────────────────────────

test('ger user off roster cannot join rtg course', function () {
    $user = gerUserOffRoster();
    $course = Course::factory()->create(['type' => 'RTG', 'min_rating' => 1, 'max_rating' => 7, 'position' => 'GND']);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You must complete roster reentry before joining other courses.');
});

test('ger user off roster can join rst course', function () {
    $user = gerUserOffRoster(['rating' => 3]);
    $course = Course::factory()->create(['type' => 'RST', 'min_rating' => 1, 'max_rating' => 7]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue()
        ->and($reason)->toBe('');
});

// ─── Visitor (non-GER on roster) ─────────────────────────────────────────────

test('visitor on roster cannot join rst course', function () {
    $user = visitorOnRoster();
    $course = Course::factory()->create(['type' => 'RST', 'min_rating' => 1, 'max_rating' => 7]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You are already on the roster and cannot join roster reentry courses.');
});

test('visitor on roster cannot join gst course because already accepted as visitor', function () {
    $user = visitorOnRoster();
    $course = Course::factory()->create(['type' => 'GST', 'min_rating' => 1, 'max_rating' => 7]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You are already accepted as a visitor and cannot join visitor courses.');
});

// ─── Foreign non-roster ───────────────────────────────────────────────────────

test('foreign non-roster user cannot join non-gst course', function () {
    $user = foreignOffRoster();
    $course = Course::factory()->create(['type' => 'RTG', 'min_rating' => 1, 'max_rating' => 7, 'position' => 'GND']);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('As a visitor, you can only join visitor courses (GST).');
});

test('foreign non-roster user can join gst course', function () {
    $user = foreignOffRoster();
    $course = Course::factory()->create(['type' => 'GST', 'min_rating' => 1, 'max_rating' => 7]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue()
        ->and($reason)->toBe('');
});

// ─── Rating checks ────────────────────────────────────────────────────────────

test('user with rating too low for course is rejected', function () {
    $user = gerUserOnRoster(['rating' => 1]);
    $course = Course::factory()->create(['type' => 'RTG', 'min_rating' => 3, 'max_rating' => 5, 'position' => 'GND']);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You do not have the required rating for this course.');
});

test('user with rating too high for course is rejected', function () {
    $user = gerUserOnRoster(['rating' => 7]);
    $course = Course::factory()->create(['type' => 'RTG', 'min_rating' => 2, 'max_rating' => 4, 'position' => 'GND']);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You do not have the required rating for this course.');
});

// ─── Active RTG course ────────────────────────────────────────────────────────

test('user with active rtg course cannot join another rtg course', function () {
    $user = gerUserOnRoster(['rating' => 3]);
    $existingCourse = Course::factory()->create(['type' => 'RTG', 'min_rating' => 1, 'max_rating' => 7, 'position' => 'GND']);
    $user->activeCourses()->attach($existingCourse->id, ['completed_at' => null]);

    Cache::flush(); // clear any cached active RTG state

    $newCourse = Course::factory()->create(['type' => 'RTG', 'min_rating' => 2, 'max_rating' => 4, 'position' => 'GND']);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($newCourse, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You already have an active RTG course.');
});

// ─── Waiting list restriction ─────────────────────────────────────────────────

test('user restricted from course type cannot join', function () {
    $user = gerUserOnRoster(['rating' => 3]);
    WaitingListRestriction::create(['user_id' => $user->id, 'type' => 'RTG', 'expires_at' => null]);

    $course = Course::factory()->create(['type' => 'RTG', 'min_rating' => 2, 'max_rating' => 4, 'position' => 'GND']);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You are currently restricted from joining this type of waiting list.');
});

// ─── S3 / APP rating change window ────────────────────────────────────────────

test('s3 user cannot join app rtg course within 90 days of rating change', function () {
    $user = gerUserOnRoster(['rating' => 3, 'last_rating_change' => now()->subDays(30)]);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'min_rating' => 3, 'max_rating' => 4]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse();
});

test('s3 user can join app rtg course after 90 days of rating change', function () {
    $user = gerUserOnRoster(['rating' => 3, 'last_rating_change' => now()->subDays(91)]);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'min_rating' => 3, 'max_rating' => 4]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue()
        ->and($reason)->toBe('');
});

// ─── Familiarisation ──────────────────────────────────────────────────────────

test('user with existing familiarisation for course sector cannot join', function () {
    $user = gerUserOnRoster(['rating' => 3]);
    $sector = FamiliarisationSector::create(['name' => 'Test Sector', 'fir' => 'EDWW']);
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $sector->id]);

    $course = Course::factory()->create([
        'type' => 'RTG',
        'position' => 'GND',
        'min_rating' => 2,
        'max_rating' => 4,
        'familiarisation_sector_id' => $sector->id,
    ]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You already have a familiarisation for this course.');
});
