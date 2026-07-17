<?php

/**
 * Unit tests for SyncWaitingListActivity — methods tested directly via reflection.
 * Complements Feature/Console/SyncWaitingListActivityTest.php which tests the full
 * artisan command pipeline. Here we focus on the position-routing logic in
 * getActivityHours() and the updateEntryActivity() DB side-effects in isolation.
 */

use App\Console\Commands\SyncWaitingListActivity;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Services\VatsimActivityService;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Cache::flush();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function wlMakeCommand(): SyncWaitingListActivity
{
    return app(SyncWaitingListActivity::class);
}

function wlSetIO(object $command): BufferedOutput
{
    $buffered = new BufferedOutput();
    $prop = new ReflectionProperty($command, 'output');
    $prop->setAccessible(true);
    $prop->setValue($command, new OutputStyle(new ArrayInput([]), $buffered));
    return $buffered;
}

function wlCallMethod(object $command, string $method, mixed ...$args): mixed
{
    $m = new ReflectionMethod($command, $method);
    $m->setAccessible(true);
    return $m->invoke($command, ...$args);
}

// ─── getActivityHours: position routing ──────────────────────────────────────

test('GND position routes to calculateS1TowerHours', function () {
    Http::fake([
        'stats.vatsim-germany.org/*' => Http::response([], 200),
        'raw.githubusercontent.com/*' => Http::response([
            ['logon' => 'EDDL_GND', 's1_twr' => true],
        ], 200),
    ]);

    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'GND', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567]);

    $cmd = wlMakeCommand();
    wlSetIO($cmd);

    $hours = wlCallMethod($cmd, 'getActivityHours', $course, $user);

    expect($hours)->toBeFloat();
});

test('TWR position routes to calculateS1TowerHours', function () {
    Http::fake([
        'stats.vatsim-germany.org/*' => Http::response([], 200),
        'raw.githubusercontent.com/*' => Http::response([], 200),
    ]);

    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'TWR', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567]);

    $cmd = wlMakeCommand();
    wlSetIO($cmd);

    $hours = wlCallMethod($cmd, 'getActivityHours', $course, $user);

    expect($hours)->toBeFloat();
});

test('APP position routes to calculateAppHours using API connections', function () {
    Http::fake([
        'stats.vatsim-germany.org/*' => Http::response([
            ['callsign' => 'EDDL_APP', 'minutes_online' => 120.0],
        ], 200),
    ]);

    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567]);

    $cmd = wlMakeCommand();
    wlSetIO($cmd);

    $hours = wlCallMethod($cmd, 'getActivityHours', $course, $user);

    expect($hours)->toBe(2.0); // 120 min / 60
});

test('APP position: only matching airport callsigns count', function () {
    Http::fake([
        'stats.vatsim-germany.org/*' => Http::response([
            ['callsign' => 'EDDL_APP', 'minutes_online' => 60.0],  // matches
            ['callsign' => 'EDDF_APP', 'minutes_online' => 60.0],  // wrong airport
            ['callsign' => 'EDDL_DEP', 'minutes_online' => 30.0],  // DEP counts
            ['callsign' => 'EDDL_TWR', 'minutes_online' => 90.0],  // TWR doesn't count for APP
        ], 200),
    ]);

    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567]);

    $cmd = wlMakeCommand();
    wlSetIO($cmd);

    $hours = wlCallMethod($cmd, 'getActivityHours', $course, $user);

    expect($hours)->toBe(1.5); // (60 + 30) / 60
});

test('CTR position always returns exactly 10', function () {
    // CTR is hardcoded — no HTTP call to the stats API is made.
    // Use stdClass to avoid the DB CHECK constraint on position.
    Http::fake(['stats.vatsim-germany.org/*' => Http::response([], 200)]);

    $user = User::factory()->create(['vatsim_id' => 1234567]);
    $course = (object)['position' => 'CTR', 'airport_icao' => 'EDWW', 'mentorGroup' => (object)['name' => 'EDWW Mentor']];

    $cmd = wlMakeCommand();
    wlSetIO($cmd);

    $hours = wlCallMethod($cmd, 'getActivityHours', $course, $user);

    // Return type is float; the literal 10 is coerced to 10.0
    expect($hours)->toBe(10.0);
});

test('unknown position returns -1', function () {
    // 'XYZ' would fail the DB CHECK constraint — use stdClass instead.
    Http::fake(['stats.vatsim-germany.org/*' => Http::response([], 200)]);

    $user = User::factory()->create(['vatsim_id' => 1234567]);
    $course = (object)['position' => 'XYZ', 'airport_icao' => 'EDDL', 'mentorGroup' => (object)['name' => 'EDGG Mentor']];

    $cmd = wlMakeCommand();
    wlSetIO($cmd);

    $hours = wlCallMethod($cmd, 'getActivityHours', $course, $user);

    expect($hours)->toBe(-1.0);
});

test('API non-200 response returns -1', function () {
    Http::fake(['stats.vatsim-germany.org/*' => Http::response('error', 500)]);

    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567]);

    $cmd = wlMakeCommand();
    wlSetIO($cmd);

    $hours = wlCallMethod($cmd, 'getActivityHours', $course, $user);

    expect($hours)->toBe(-1.0);
});

// ─── updateEntryActivity: DB side-effects ─────────────────────────────────────

test('updateEntryActivity writes calculated hours and updates hours_updated', function () {
    Http::fake([
        'stats.vatsim-germany.org/*' => Http::response([
            ['callsign' => 'EDDL_APP', 'minutes_online' => 180.0],
        ], 200),
    ]);

    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567, 'rating' => 3, 'last_known_rating' => 3]);

    $entry = WaitingListEntry::create([
        'user_id'      => $user->id,
        'course_id'    => $course->id,
        'date_added'   => now(),
        'activity'     => 0.0,
        'hours_updated'=> now()->subDay(),
    ]);

    $oldUpdated = $entry->hours_updated->copy();

    $cmd = wlMakeCommand();
    wlSetIO($cmd);
    wlCallMethod($cmd, 'updateEntryActivity', $entry);

    $entry->refresh();
    expect($entry->activity)->toBe(3.0) // 180 / 60
        ->and($entry->hours_updated->greaterThan($oldUpdated))->toBeTrue();
});

test('updateEntryActivity does not update a non-VATSIM user entry', function () {
    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 0, 'rating' => 3, 'last_known_rating' => 3]);

    $entry = WaitingListEntry::create([
        'user_id'      => $user->id,
        'course_id'    => $course->id,
        'date_added'   => now(),
        'activity'     => 7.5,
        'hours_updated'=> now()->subDay(),
    ]);

    $cmd = wlMakeCommand();
    wlSetIO($cmd);
    wlCallMethod($cmd, 'updateEntryActivity', $entry);

    $entry->refresh();
    expect($entry->activity)->toBe(7.5); // unchanged
});

test('updateEntryActivity handles API exception without crashing', function () {
    Http::fake(['*' => fn() => throw new \Exception('Connection refused')]);

    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567, 'rating' => 3, 'last_known_rating' => 3]);

    $entry = WaitingListEntry::create([
        'user_id'      => $user->id,
        'course_id'    => $course->id,
        'date_added'   => now(),
        'activity'     => 5.0,
        'hours_updated'=> now(),
    ]);

    $cmd = wlMakeCommand();
    wlSetIO($cmd);

    // Should not throw — exception is caught internally
    wlCallMethod($cmd, 'updateEntryActivity', $entry);
    expect(true)->toBeTrue();
});
