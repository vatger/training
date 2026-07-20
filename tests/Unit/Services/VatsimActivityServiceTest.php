<?php

use App\Services\VatsimActivityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::flush();
});

function activityService(): VatsimActivityService
{
    return app(VatsimActivityService::class);
}

function invokeCalculateActivity(array $endorsement, array $connections): array
{
    $service = activityService();
    $method = new ReflectionMethod($service, 'calculateActivity');
    $method->setAccessible(true);

    return $method->invoke($service, $endorsement, $connections);
}

// ─── calculateActivity: TWR endorsement ──────────────────────────────────────

test('twr endorsement: matching twr callsign counts minutes', function () {
    $endorsement = ['user_cid' => 1234567, 'position' => 'EDDL_TWR'];
    $connections = [
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 60.0, 'disconnected_at' => '2025-10-01T12:00:00Z'],
        ['callsign' => 'EDDL_1_TWR', 'minutes_online' => 30.0, 'disconnected_at' => '2025-10-02T19:35:00Z'],
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 30.0, 'disconnected_at' => '2025-10-03T20:00:00Z'],
    ];

    $result = invokeCalculateActivity($endorsement, $connections);

    expect($result['minutes'])->toBe(120.0);
});

test('twr endorsement: app callsign counts (topdown)', function () {
    $endorsement = ['user_cid' => 1234567, 'position' => 'EDDL_TWR'];
    $connections = [
        ['callsign' => 'EDDL_APP', 'minutes_online' => 60.0, 'disconnected_at' => '2025-10-01T12:00:00Z'],
        ['callsign' => 'EDDL_1_APP', 'minutes_online' => 60.0, 'disconnected_at' => '2025-10-02T19:35:00Z'],
        ['callsign' => 'EDDL__APP', 'minutes_online' => 60.0, 'disconnected_at' => '2025-10-03T20:00:00Z'],
    ];

    $result = invokeCalculateActivity($endorsement, $connections);

    expect($result['minutes'])->toBe(180.0);
});

test('twr endorsement: dep callsign counts (topdown)', function () {
    $endorsement = ['user_cid' => 1234567, 'position' => 'EDDL_TWR'];
    $connections = [
        ['callsign' => 'EDDL_DEP', 'minutes_online' => 45.0, 'disconnected_at' => '2025-10-01T12:00:00Z'],
    ];

    $result = invokeCalculateActivity($endorsement, $connections);

    expect($result['minutes'])->toBe(45.0);
});

test('twr endorsement: del callsign does not count', function () {
    $endorsement = ['user_cid' => 1234567, 'position' => 'EDDL_TWR'];
    $connections = [
        ['callsign' => 'EDDL_DEL', 'minutes_online' => 90.0, 'disconnected_at' => '2025-10-01T12:00:00Z'],
    ];

    $result = invokeCalculateActivity($endorsement, $connections);

    expect($result['minutes'])->toBe(0);
});

// ─── calculateActivity: CTR endorsement ──────────────────────────────────────

test('ctr endorsement: callsign starting with ctr prefix counts', function () {
    $endorsement = ['user_cid' => 1234567, 'position' => 'EDWW_N_CTR'];
    $connections = [
        ['callsign' => 'EDWW_N_CTR', 'minutes_online' => 100.0, 'disconnected_at' => '2025-10-01T12:00:00Z'],
    ];

    $result = invokeCalculateActivity($endorsement, $connections);

    expect($result['minutes'])->toBe(100.0);
});

test('ctr endorsement edww_w: edww_ctr callsign counts', function () {
    $endorsement = ['user_cid' => 1234567, 'position' => 'EDWW_W_CTR'];
    $connections = [
        ['callsign' => 'EDWW_CTR', 'minutes_online' => 75.0, 'disconnected_at' => '2025-10-01T12:00:00Z'],
    ];

    $result = invokeCalculateActivity($endorsement, $connections);

    expect($result['minutes'])->toBe(75.0);
});

// ─── calculateActivity: multiple connections ──────────────────────────────────

test('multiple matching connections sum minutes', function () {
    $endorsement = ['user_cid' => 1234567, 'position' => 'EDDL_TWR'];
    $connections = [
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 60.0, 'disconnected_at' => '2025-09-01T10:00:00Z'],
        ['callsign' => 'EDDL_APP', 'minutes_online' => 90.0, 'disconnected_at' => '2025-10-01T12:00:00Z'],
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 30.0, 'disconnected_at' => '2025-10-15T08:00:00Z'],
    ];

    $result = invokeCalculateActivity($endorsement, $connections);

    expect($result['minutes'])->toBe(180.0);
});

// ─── calculateActivity: empty connections ─────────────────────────────────────

test('empty connections return zero minutes and null last activity date', function () {
    $endorsement = ['user_cid' => 1234567, 'position' => 'EDDL_TWR'];

    $result = invokeCalculateActivity($endorsement, []);

    expect($result['minutes'])->toBe(0)
        ->and($result['last_activity_date'])->toBeNull();
});

// ─── calculateActivity: last_activity_date ───────────────────────────────────

test('last_activity_date is set to most recent disconnected_at', function () {
    $endorsement = ['user_cid' => 1234567, 'position' => 'EDDL_TWR'];
    $connections = [
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 60.0, 'disconnected_at' => '2025-09-01T10:00:00Z'],
        ['callsign' => 'EDDL_TWR', 'minutes_online' => 30.0, 'disconnected_at' => '2025-10-15T08:00:00Z'],
    ];

    $result = invokeCalculateActivity($endorsement, $connections);

    expect($result['last_activity_date'])->toBeInstanceOf(Carbon::class)
        ->and($result['last_activity_date']->format('Y-m-d'))->toBe('2025-10-15');
});

// ─── getActivityStatus ────────────────────────────────────────────────────────

test('getActivityStatus returns active when at or above threshold', function () {
    expect(activityService()->getActivityStatus(180.0))->toBe('active');
});

test('getActivityStatus returns warning when at 50 percent of threshold', function () {
    expect(activityService()->getActivityStatus(90.0))->toBe('warning');
});

test('getActivityStatus returns removal when below warning threshold', function () {
    expect(activityService()->getActivityStatus(0.0))->toBe('removal');
});

// ─── getActivityProgress ──────────────────────────────────────────────────────

test('getActivityProgress returns 100 at threshold', function () {
    expect(activityService()->getActivityProgress(180.0))->toBe(100.0);
});

test('getActivityProgress returns 50 at half threshold', function () {
    expect(activityService()->getActivityProgress(90.0))->toBe(50.0);
});

test('getActivityProgress is capped at 100 above threshold', function () {
    expect(activityService()->getActivityProgress(200.0))->toBe(100.0);
});
