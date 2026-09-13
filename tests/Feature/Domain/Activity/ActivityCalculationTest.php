<?php

use App\Domain\Activity\Actions\CalculateControllerActivity;
use App\Domain\Activity\Actions\CalculateStationActivity;
use App\Domain\Activity\Reference\ActivityDataMirror;
use App\Integrations\VatsimGermanyStats\FakeVatsimGermanyStatsClient;
use App\Integrations\VatsimGermanyStats\VatsimGermanyStatsClientInterface;
use App\Models\ControllerActivity;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    Event::fake();
    Storage::fake('local');
    config()->set('activity.mirror.disk', 'local');
    config()->set('activity.sources.sessions.callsign_prefixes', []);
    config()->set('activity.sources.sessions.history_since', CarbonImmutable::now()->subDays(240)->toDateString());
    app(ActivityDataMirror::class)->sync();

    $this->stats = new FakeVatsimGermanyStatsClient;
    app()->instance(VatsimGermanyStatsClientInterface::class, $this->stats);
});

function seedActivitySession(int $cid, string $callsign, string $from, string $to): void
{
    $start = CarbonImmutable::parse($from);
    $end = CarbonImmutable::parse($to);

    test()->stats->fake($cid, [[
        'id' => random_int(1, PHP_INT_MAX),
        'callsign' => $callsign,
        'frequency' => null,
        'facility_type' => 6,
        'connected_at' => $start->toIso8601String(),
        'disconnected_at' => $end->toIso8601String(),
        'minutes_online' => max(1, (int) abs($end->diffInMinutes($start))),
    ]]);
}

it('credits a lone controller full minutes on every station they top-down', function () {
    $day = CarbonImmutable::now()->subDays(5)->setTime(10, 0);
    seedActivitySession(1001, 'EDGG_GIN_CTR', $day->toIso8601String(), $day->addHours(2)->toIso8601String());

    $results = app(CalculateControllerActivity::class)->execute(1001);

    expect($results['EDGG_GIN_CTR']->minutes)->toEqualWithDelta(120, 0.5)
        ->and($results['EDGG_HEF_CTR']->minutes)->toEqualWithDelta(120, 0.5)
        ->and($results['EDGG_SIG_CTR']->minutes)->toEqualWithDelta(120, 0.5);

    expect(ControllerActivity::forController(1001)->forStation('EDGG_GIN_CTR')->first()->minutes)
        ->toEqualWithDelta(120, 0.5);
});

it('splits activity between two controllers by exact live ownership', function () {
    $base = CarbonImmutable::now()->subDays(5)->setTime(10, 0);
    seedActivitySession(1001, 'EDGG_GIN_CTR', $base->toIso8601String(), $base->addHours(2)->toIso8601String());
    seedActivitySession(1002, 'EDGG_HEF_CTR', $base->addMinutes(30)->toIso8601String(), $base->addMinutes(90)->toIso8601String());

    $gin = app(CalculateControllerActivity::class)->execute(1001);
    $hef = app(CalculateControllerActivity::class)->execute(1002);

    expect($gin['EDGG_GIN_CTR']->minutes)->toEqualWithDelta(120, 0.5);
    expect($gin['EDGG_HEF_CTR']->minutes)->toEqualWithDelta(60, 0.5);
    expect($hef['EDGG_HEF_CTR']->minutes)->toEqualWithDelta(60, 0.5);
    expect($hef)->not->toHaveKey('EDGG_GIN_CTR')
        ->and(ControllerActivity::forController(1002)->forStation('EDGG_GIN_CTR')->exists())->toBeFalse();
});

it('reports eligible-since once a controller has dropped below the minimum', function () {
    config()->set('activity.min_minutes', 180);

    $old = CarbonImmutable::now()->subDays(200)->setTime(12, 0);
    $end = $old->addHours(4);
    seedActivitySession(1001, 'EDGG_GIN_CTR', $old->toIso8601String(), $end->toIso8601String());

    $result = app(CalculateStationActivity::class)->execute(1001, 'EDGG_GIN_CTR');

    expect($result->minutes)->toEqualWithDelta(0, 0.5)
        ->and($result->eligibleSince)->not->toBeNull()
        ->and($result->eligibleSince->toDateString())->toBe($end->addDays(180)->toDateString());
});

it('drops a station row when activity falls to zero on recalculation', function () {
    $day = CarbonImmutable::now()->subDays(5)->setTime(10, 0);
    seedActivitySession(1001, 'EDGG_GIN_CTR', $day->toIso8601String(), $day->addHour()->toIso8601String());
    app(CalculateControllerActivity::class)->execute(1001);
    expect(ControllerActivity::forController(1001)->forStation('EDGG_SIG_CTR')->exists())->toBeTrue();

    $this->stats = new FakeVatsimGermanyStatsClient;
    app()->instance(VatsimGermanyStatsClientInterface::class, $this->stats);
    Cache::flush();
    app(ActivityDataMirror::class)->sync();

    app(CalculateControllerActivity::class)->execute(1001);
    expect(ControllerActivity::forController(1001)->exists())->toBeFalse();
});
