<?php

use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\Course;
use App\Models\Familiarisation;
use App\Models\FamiliarisationSector;
use App\Models\User;
use App\Models\WaitingListRestriction;
use App\Services\CourseValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Cache::flush();
});

function fakeRosterWithIds(array $vatsimIds): void
{
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getRoster')->andReturn($vatsimIds);
    app()->instance(VatEudClientInterface::class, $client);
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

// ─── Active trainee of the same course ────────────────────────────────────────

test('user actively training in an endorsement course cannot join its waiting list again', function () {
    $user = gerUserOnRoster(['rating' => 3]);
    $course = Course::factory()->create(['type' => 'EDMT', 'min_rating' => 1, 'max_rating' => 7, 'position' => 'GND']);
    $user->activeCourses()->attach($course->id, ['completed_at' => null]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('You are already an active trainee in this course.');
});

test('user with completed enrollment in a course can join its waiting list again', function () {
    $user = gerUserOnRoster(['rating' => 3]);
    $course = Course::factory()->create(['type' => 'EDMT', 'min_rating' => 1, 'max_rating' => 7, 'position' => 'GND']);
    $user->courses()->attach($course->id, ['completed_at' => now()]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue()
        ->and($reason)->toBe('');
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

// ─── 90-day rating change waiting period (all RTG courses) ───────────────────

test('user cannot join any rtg course within 90 days of rating change', function () {
    $user = gerUserOnRoster(['rating' => 2, 'last_rating_change' => now()->subDays(30)]);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'GND', 'min_rating' => 1, 'max_rating' => 2]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('Your last rating change was less than 3 months ago. You cannot join a new rating course yet.');
});

test('s3 user cannot join app rtg course within 90 days of rating change', function () {
    $user = gerUserOnRoster(['rating' => 3, 'last_rating_change' => now()->subDays(30)]);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'min_rating' => 3, 'max_rating' => 4]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('Your last rating change was less than 3 months ago. You cannot join a new rating course yet.');
});

test('user can join rtg course after 90 days of rating change', function () {
    $user = gerUserOnRoster(['rating' => 3, 'last_rating_change' => now()->subDays(91)]);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'min_rating' => 3, 'max_rating' => 4]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue()
        ->and($reason)->toBe('');
});

test('user with no last_rating_change can join rtg course without waiting period', function () {
    $user = gerUserOnRoster(['rating' => 3, 'last_rating_change' => null]);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'TWR', 'min_rating' => 2, 'max_rating' => 3]);

    [$canJoin] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue();
});

test('user cannot join rtg course at exactly 89 days after rating change', function () {
    $user = gerUserOnRoster(['rating' => 3, 'last_rating_change' => now()->subDays(89)]);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'GND', 'min_rating' => 2, 'max_rating' => 4]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe('Your last rating change was less than 3 months ago. You cannot join a new rating course yet.');
});

test('user can join rtg course at exactly 90 days after rating change', function () {
    $user = gerUserOnRoster(['rating' => 3, 'last_rating_change' => now()->subDays(90)]);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'GND', 'min_rating' => 2, 'max_rating' => 4]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue()
        ->and($reason)->toBe('');
});

test('recent rating change does not block joining non-rtg courses', function () {
    $user = gerUserOnRoster(['rating' => 3, 'last_rating_change' => now()->subDays(30)]);
    $course = Course::factory()->create(['type' => 'EDMT', 'min_rating' => 1, 'max_rating' => 7]);

    [$canJoin] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue();
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

// ─── Required familiarisations (CTR endorsement courses) ─────────────────────

test('user missing a required familiarisation cannot join ctr edmt course', function () {
    $user = gerUserOnRoster(['rating' => 5]);
    $wld = FamiliarisationSector::create(['name' => 'WLD', 'fir' => 'EDGG']);
    $sta = FamiliarisationSector::create(['name' => 'STA', 'fir' => 'EDGG']);
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $wld->id]);

    $course = Course::factory()->create([
        'type' => 'EDMT',
        'position' => 'CTR',
        'min_rating' => 5,
        'max_rating' => 7,
    ]);
    $course->requiredFamiliarisationSectors()->attach([$wld->id, $sta->id]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeFalse()
        ->and($reason)->toBe("You need the following familiarisation(s) before joining this course's waiting list: STA.");
});

test('user holding all required familiarisations can join ctr edmt course', function () {
    $user = gerUserOnRoster(['rating' => 5]);
    $wld = FamiliarisationSector::create(['name' => 'WLD', 'fir' => 'EDGG']);
    $sta = FamiliarisationSector::create(['name' => 'STA', 'fir' => 'EDGG']);
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $wld->id]);
    Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $sta->id]);

    $course = Course::factory()->create([
        'type' => 'EDMT',
        'position' => 'CTR',
        'min_rating' => 5,
        'max_rating' => 7,
    ]);
    $course->requiredFamiliarisationSectors()->attach([$wld->id, $sta->id]);

    [$canJoin, $reason] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue()
        ->and($reason)->toBe('');
});

test('ctr edmt course without required familiarisations configured is unaffected', function () {
    $user = gerUserOnRoster(['rating' => 5]);

    $course = Course::factory()->create([
        'type' => 'EDMT',
        'position' => 'CTR',
        'min_rating' => 5,
        'max_rating' => 7,
    ]);

    [$canJoin] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue();
});

test('required familiarisations do not apply to ctr rtg courses', function () {
    $user = gerUserOnRoster(['rating' => 5]);
    $sector = FamiliarisationSector::create(['name' => 'WLD', 'fir' => 'EDGG']);

    $course = Course::factory()->create([
        'type' => 'RTG',
        'position' => 'CTR',
        'min_rating' => 5,
        'max_rating' => 7,
    ]);
    $course->requiredFamiliarisationSectors()->attach($sector->id);

    [$canJoin] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue();
});

test('required familiarisations do not apply to non-ctr edmt courses', function () {
    $user = gerUserOnRoster(['rating' => 5]);
    $sector = FamiliarisationSector::create(['name' => 'WLD', 'fir' => 'EDGG']);

    $course = Course::factory()->create([
        'type' => 'EDMT',
        'position' => 'APP',
        'min_rating' => 5,
        'max_rating' => 7,
    ]);
    $course->requiredFamiliarisationSectors()->attach($sector->id);

    [$canJoin] = makeService()->canUserJoinCourse($course, $user);

    expect($canJoin)->toBeTrue();
});
