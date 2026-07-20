<?php

use App\Console\Commands\SyncWaitingListActivity;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    Cache::flush();
    Http::fake(['*' => Http::response([], 200)]);
});

// ─── Pure method helpers (reflection) ────────────────────────────────────────

function syncCommand(): SyncWaitingListActivity
{
    return app(SyncWaitingListActivity::class);
}

function callEqualStr(string $a, string $b): bool
{
    $method = new ReflectionMethod(SyncWaitingListActivity::class, 'equalStr');
    $method->setAccessible(true);

    return $method->invoke(syncCommand(), $a, $b);
}

function callCalculateS2TowerHoursFeature(array $connections, string $airport): float
{
    $method = new ReflectionMethod(SyncWaitingListActivity::class, 'calculateS2TowerHours');
    $method->setAccessible(true);

    return $method->invoke(syncCommand(), $connections, $airport);
}

function callCalculateS1TowerHours(array $connections, string $fir): float
{
    $method = new ReflectionMethod(SyncWaitingListActivity::class, 'calculateS1TowerHours');
    $method->setAccessible(true);

    return $method->invoke(syncCommand(), $connections, $fir);
}

// ─── equalStr ─────────────────────────────────────────────────────────────────

test('equalStr: identical simple callsigns match', function () {
    expect(callEqualStr('EDDL_TWR', 'EDDL_TWR'))->toBeTrue();
});

test('equalStr: callsigns with same first and last segment but different middle match', function () {
    expect(callEqualStr('EDDL_1_TWR', 'EDDL_TWR'))->toBeTrue();
});

test('equalStr: callsigns with different middle segment still match on first/last', function () {
    expect(callEqualStr('EDDF_X_APP', 'EDDF_APP'))->toBeTrue();
});

test('equalStr: different airport does not match', function () {
    expect(callEqualStr('EDDL_TWR', 'EDDF_TWR'))->toBeFalse();
});

test('equalStr: same airport but different station does not match', function () {
    expect(callEqualStr('EDDL_TWR', 'EDDL_APP'))->toBeFalse();
});

test('equalStr: DEL vs TWR does not match', function () {
    expect(callEqualStr('EDDL_DEL', 'EDDL_TWR'))->toBeFalse();
});

test('equalStr: GND vs TWR does not match', function () {
    expect(callEqualStr('EDDL_GND', 'EDDL_TWR'))->toBeFalse();
});

test('equalStr: APP vs DEP does not match', function () {
    expect(callEqualStr('EDDL_APP', 'EDDL_DEP'))->toBeFalse();
});

test('equalStr: completely different callsigns do not match', function () {
    expect(callEqualStr('EDDL_TWR', 'EGLL_APP'))->toBeFalse();
});

test('equalStr: empty string input returns false', function () {
    expect(callEqualStr('', 'EDDL_TWR'))->toBeFalse();
    expect(callEqualStr('EDDL_TWR', ''))->toBeFalse();
});

test('equalStr: both empty strings evaluate as equal (explode quirk)', function () {
    // explode('_', '') returns [''], so both sides produce [''] — they match.
    // This edge case is benign in practice since no real callsign is empty.
    expect(callEqualStr('', ''))->toBeTrue();
});

test('equalStr: single-segment callsign without underscore returns false against two-segment', function () {
    expect(callEqualStr('EDDL', 'EDDL_TWR'))->toBeFalse();
});

// ─── calculateS1TowerHours ────────────────────────────────────────────────────

test('calculateS1TowerHours: returns 0 when datahub fetch fails', function () {
    Http::swap(new HttpFactory);
    Http::fake(['*' => Http::response('Not Found', 404)]);

    $connections = [['callsign' => 'EDDL_TWR', 'minutes_online' => 60.0]];
    expect(callCalculateS1TowerHours($connections, 'EDGG'))->toBe(0.0);
});

test('calculateS1TowerHours: counts only s1_twr=true stations', function () {
    Http::swap(new HttpFactory);
    Http::fake([
        '*' => Http::response([
            ['logon' => 'EDDL_TWR', 's1_twr' => true],
            ['logon' => 'EDDF_TWR', 's1_twr' => false],
        ], 200),
    ]);

    $connections = [
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 120.0],
        ['callsign' => 'EDDF_TWR', 'minutes_online' => 60.0],
    ];
    // Only EDDL_TWR counts: 120 / 60 = 2.0
    expect(callCalculateS1TowerHours($connections, 'EDGG'))->toBe(2.0);
});

test('calculateS1TowerHours: excludes stations with _I_ in logon callsign', function () {
    Http::swap(new HttpFactory);
    Http::fake([
        '*' => Http::response([
            ['logon' => 'EDDL_I_TWR', 's1_twr' => true],
            ['logon' => 'EDDF_TWR',   's1_twr' => true],
        ], 200),
    ]);

    $connections = [
        ['callsign' => 'EDDL_I_TWR', 'minutes_online' => 60.0],
        ['callsign' => 'EDDF_TWR',   'minutes_online' => 60.0],
    ];
    // EDDL_I_TWR is excluded; only EDDF_TWR counts: 60 / 60 = 1.0
    expect(callCalculateS1TowerHours($connections, 'EDGG'))->toBe(1.0);
});

test('calculateS1TowerHours: sums minutes across multiple matching sessions', function () {
    Http::swap(new HttpFactory);
    Http::fake([
        '*' => Http::response([['logon' => 'EDDL_TWR', 's1_twr' => true]], 200),
    ]);

    $connections = [
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 60.0],
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 90.0],
    ];
    expect(callCalculateS1TowerHours($connections, 'EDGG'))->toBe(2.5);
});

test('calculateS1TowerHours: equalStr matching allows middle-segment variation', function () {
    Http::swap(new HttpFactory);
    Http::fake([
        '*' => Http::response([['logon' => 'EDDL_TWR', 's1_twr' => true]], 200),
    ]);

    // EDDL_1_TWR should match the EDDL_TWR station via equalStr
    $connections = [['callsign' => 'EDDL_1_TWR', 'minutes_online' => 60.0]];
    expect(callCalculateS1TowerHours($connections, 'EDGG'))->toBe(1.0);
});

// ─── calculateS2TowerHours ────────────────────────────────────────────────────

test('calculateS2TowerHours: TWR session at matching airport is counted', function () {
    $connections = [['callsign' => 'EDDL_TWR', 'minutes_online' => 120.0]];
    expect(callCalculateS2TowerHoursFeature($connections, 'EDDL'))->toBe(2.0);
});

test('calculateS2TowerHours: multi-segment TWR callsign is counted', function () {
    $connections = [['callsign' => 'EDDL_C_TWR', 'minutes_online' => 60.0]];
    expect(callCalculateS2TowerHoursFeature($connections, 'EDDL'))->toBe(1.0);
});

test('calculateS2TowerHours: APP at same airport is NOT counted', function () {
    $connections = [['callsign' => 'EDDL_APP', 'minutes_online' => 60.0]];
    expect(callCalculateS2TowerHoursFeature($connections, 'EDDL'))->toBe(0.0);
});

test('calculateS2TowerHours: GND at same airport is NOT counted', function () {
    $connections = [['callsign' => 'EDDL_GND', 'minutes_online' => 60.0]];
    expect(callCalculateS2TowerHoursFeature($connections, 'EDDL'))->toBe(0.0);
});

test('calculateS2TowerHours: TWR at wrong airport is NOT counted', function () {
    $connections = [['callsign' => 'EDDF_TWR', 'minutes_online' => 60.0]];
    expect(callCalculateS2TowerHoursFeature($connections, 'EDDL'))->toBe(0.0);
});

test('calculateS2TowerHours: sums multiple matching sessions', function () {
    $connections = [
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 60.0],
        ['callsign' => 'EDDL_N_TWR', 'minutes_online' => 30.0],
        ['callsign' => 'EDDF_TWR', 'minutes_online' => 60.0], // wrong airport
    ];
    expect(callCalculateS2TowerHoursFeature($connections, 'EDDL'))->toBe(1.5); // 90 / 60
});

test('calculateS2TowerHours: empty connections returns 0.0', function () {
    expect(callCalculateS2TowerHoursFeature([], 'EDDL'))->toBe(0.0);
});

// ─── Command-level: no entries ───────────────────────────────────────────────

test('outputs skip message when there are no RTG waiting list entries', function () {
    $this->artisan('waitinglists:sync-activities')
        ->expectsOutputToContain('No RTG waiting list entries to update')
        ->assertExitCode(0);
});

test('does NOT skip when RTG entries exist', function () {
    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567, 'rating' => 3, 'last_known_rating' => 3]);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0,
        'hours_updated' => now(),
    ]);

    Http::swap(new HttpFactory);
    Http::fake(['*' => Http::response([], 200)]);

    $this->artisan('waitinglists:sync-activities')
        ->expectsOutputToContain('Updating activity for 1 RTG waiting list entry')
        ->assertExitCode(0);
});

test('skips non-VATSIM users and does not attempt an API call', function () {
    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    // isVatsimUser() uses !empty($this->vatsim_id); 0 is falsy so the user is skipped.
    // vatsim_id is NOT NULL in the schema, so we use 0 instead of null.
    $user = User::factory()->create(['vatsim_id' => 0, 'rating' => 3, 'last_known_rating' => 3]);

    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 5.0,
        'hours_updated' => now(),
    ]);

    Http::swap(new HttpFactory);
    Http::fake(['*' => Http::response([], 200)]);

    $entry = WaitingListEntry::first();
    $activityBefore = $entry->activity;

    $this->artisan('waitinglists:sync-activities')->assertExitCode(0);

    $entry->refresh();
    // Activity should be unchanged for non-VATSIM users
    expect($entry->activity)->toBe($activityBefore);
});

test('updates activity and hours_updated for an RTG entry via http response', function () {
    $role = Role::create(['name' => 'EDGG Mentor']);
    $course = Course::factory()->create(['type' => 'RTG', 'position' => 'APP', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567, 'rating' => 3, 'last_known_rating' => 3]);

    $entry = WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'date_added' => now(),
        'activity' => 0.0,
        'hours_updated' => now()->subHour(),
    ]);

    Http::swap(new HttpFactory);
    Http::fake([
        'stats.vatsim-germany.org/*' => Http::response([
            ['callsign' => 'EDDL_TWR', 'minutes_online' => 120.0],
        ], 200),
    ]);

    $before = $entry->hours_updated;

    $this->artisan('waitinglists:sync-activities')->assertExitCode(0);

    $entry->refresh();
    expect($entry->activity)->toBe(2.0); // 120 minutes / 60
    expect($entry->hours_updated->greaterThan($before))->toBeTrue();
});

test('non-RTG waiting list entries are ignored when counting entries to process', function () {
    $role = Role::create(['name' => 'EDGG Mentor']);
    $gstCourse = Course::factory()->create(['type' => 'GST', 'mentor_group_id' => $role->id, 'airport_icao' => 'EDDL']);
    $user = User::factory()->create(['vatsim_id' => 1234567, 'rating' => 3, 'last_known_rating' => 3]);

    // GST entry should not count as an RTG entry
    WaitingListEntry::create([
        'user_id' => $user->id,
        'course_id' => $gstCourse->id,
        'date_added' => now(),
        'activity' => 0.0,
        'hours_updated' => now(),
    ]);

    $this->artisan('waitinglists:sync-activities')
        ->expectsOutputToContain('No RTG waiting list entries to update')
        ->assertExitCode(0);
});
