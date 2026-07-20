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

test('APP position routes to calculateS2TowerHours using TWR sessions', function () {
    Http::fake([
        'stats.vatsim-germany.org/*' => Http::response([
            ['callsign' => 'EDDL_TWR', 'minutes_online' => 120.0],
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

test('APP position: only TWR sessions at matching airport count', function () {
    Http::fake([
        'stats.vatsim-germany.org/*' => Http::response([
            ['callsign' => 'EDDL_TWR', 'minutes_online' => 90.0],  // counts
            ['callsign' => 'EDDL_APP', 'minutes_online' => 60.0],  // wrong suffix
            ['callsign' => 'EDDF_TWR', 'minutes_online' => 60.0],  // wrong airport
        ], 200),
    ]);

    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567]);

    $cmd = wlMakeCommand();
    wlSetIO($cmd);

    $hours = wlCallMethod($cmd, 'getActivityHours', $course, $user);

    expect($hours)->toBe(1.5); // 90 / 60
});

test('CTR position returns -1 (no activity requirement)', function () {
    Http::fake(['stats.vatsim-germany.org/*' => Http::response([], 200)]);

    $user = User::factory()->create(['vatsim_id' => 1234567]);
    $course = (object)['position' => 'CTR', 'airport_icao' => 'EDGG', 'mentorGroup' => (object)['name' => 'EDGG Mentor']];

    $cmd = wlMakeCommand();
    wlSetIO($cmd);

    $hours = wlCallMethod($cmd, 'getActivityHours', $course, $user);

    expect($hours)->toBe(-1.0);
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
            ['callsign' => 'EDDL_TWR', 'minutes_online' => 180.0],
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

// ─── calculateS2TowerHours ────────────────────────────────────────────────────

function callCalculateS2TowerHours(array $connections, string $airport): float
{
    $method = new ReflectionMethod(SyncWaitingListActivity::class, 'calculateS2TowerHours');
    $method->setAccessible(true);
    return $method->invoke(wlMakeCommand(), $connections, $airport);
}

test('calculateS2TowerHours: TWR session at matching airport is counted', function () {
    $connections = [['callsign' => 'EDDL_TWR', 'minutes_online' => 120.0]];
    expect(callCalculateS2TowerHours($connections, 'EDDL'))->toBe(2.0);
});

test('calculateS2TowerHours: multi-segment callsign EDDL_1_TWR counts', function () {
    $connections = [['callsign' => 'EDDL_1_TWR', 'minutes_online' => 60.0]];
    expect(callCalculateS2TowerHours($connections, 'EDDL'))->toBe(1.0);
});

test('calculateS2TowerHours: APP suffix at same airport is NOT counted', function () {
    $connections = [['callsign' => 'EDDL_APP', 'minutes_online' => 60.0]];
    expect(callCalculateS2TowerHours($connections, 'EDDL'))->toBe(0.0);
});

test('calculateS2TowerHours: GND suffix at same airport is NOT counted', function () {
    $connections = [['callsign' => 'EDDL_GND', 'minutes_online' => 60.0]];
    expect(callCalculateS2TowerHours($connections, 'EDDL'))->toBe(0.0);
});

test('calculateS2TowerHours: TWR at wrong airport is NOT counted', function () {
    $connections = [['callsign' => 'EDDF_TWR', 'minutes_online' => 60.0]];
    expect(callCalculateS2TowerHours($connections, 'EDDL'))->toBe(0.0);
});

test('calculateS2TowerHours: sums multiple matching sessions', function () {
    $connections = [
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 60.0],
        ['callsign' => 'EDDL_C_TWR', 'minutes_online' => 30.0],
        ['callsign' => 'EDDF_TWR', 'minutes_online' => 60.0], // wrong airport
    ];
    expect(callCalculateS2TowerHours($connections, 'EDDL'))->toBe(1.5); // 90 / 60
});

test('calculateS2TowerHours: empty connections returns 0.0', function () {
    expect(callCalculateS2TowerHours([], 'EDDL'))->toBe(0.0);
});

// ─── updateEntryActivity: DB side-effects ─────────────────────────────────────

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
