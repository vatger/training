<?php

use App\Domain\Activity\Reference\ActivityDataMirror;
use App\Domain\Activity\Timeline\OwnershipTimelineBuilder;
use App\Integrations\VatsimGermanyStats\FakeVatsimGermanyStatsClient;
use App\Integrations\VatsimGermanyStats\VatsimGermanyStatsClientInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
    Storage::fake('local');
    config()->set('activity.mirror.disk', 'local');
    config()->set('activity.sources.sessions.callsign_prefixes', []);
    config()->set('activity.sources.sessions.history_since', CarbonImmutable::now()->subDays(30)->toDateString());
    app(ActivityDataMirror::class)->sync();

    $this->stats = new FakeVatsimGermanyStatsClient;
    app()->instance(VatsimGermanyStatsClientInterface::class, $this->stats);
});

function seedSession(int $cid, string $callsign, string $from, string $to, ?float $freq = null): void
{
    $minutes = (int) abs(CarbonImmutable::parse($to)->diffInMinutes(CarbonImmutable::parse($from)));

    test()->stats->fake($cid, [[
        'id' => random_int(1, PHP_INT_MAX),
        'callsign' => $callsign,
        'frequency' => $freq,
        'facility_type' => 6,
        'connected_at' => $from,
        'disconnected_at' => $to,
        'minutes_online' => max(1, $minutes),
    ]]);
}

/**
 * @return list<array{cid:int,start:string,end:string}>
 */
function intervalsFor(array $timeline, string $key): array
{
    return collect($timeline)
        ->filter(fn ($i) => $i->key === $key)
        ->map(fn ($i) => ['cid' => $i->cid, 'start' => $i->start->format('H:i'), 'end' => $i->end->format('H:i')])
        ->sortBy(['start', 'cid'])
        ->values()
        ->all();
}

function timelineForDay(string $date): array
{
    return app(OwnershipTimelineBuilder::class)->build(
        CarbonImmutable::parse("{$date}T00:00:00Z"),
        CarbonImmutable::parse("{$date}T00:00:00Z")->addDay(),
    );
}

it('gives a lone controller every sector their position can top-down', function () {
    $date = CarbonImmutable::now()->subDays(3)->toDateString();
    seedSession(1001, 'EDGG_GIN_CTR', "{$date}T10:00:00Z", "{$date}T12:00:00Z");

    $timeline = timelineForDay($date);

    foreach (['GIN', 'GINH', 'HEF', 'GED', 'SIG', 'TAU'] as $sector) {
        expect(intervalsFor($timeline, $sector))->toBe([['cid' => 1001, 'start' => '10:00', 'end' => '12:00']]);
    }
});

it('splits ownership between two controllers by exact sector priority', function () {
    $date = CarbonImmutable::now()->subDays(3)->toDateString();
    seedSession(1001, 'EDGG_GIN_CTR', "{$date}T10:00:00Z", "{$date}T12:00:00Z");
    seedSession(1002, 'EDGG_HEF_CTR', "{$date}T10:30:00Z", "{$date}T11:30:00Z");

    $timeline = timelineForDay($date);

    expect(intervalsFor($timeline, 'HEF'))->toBe([
        ['cid' => 1001, 'start' => '10:00', 'end' => '10:30'],
        ['cid' => 1002, 'start' => '10:30', 'end' => '11:30'],
        ['cid' => 1001, 'start' => '11:30', 'end' => '12:00'],
    ]);

    expect(intervalsFor($timeline, 'GED'))->toBe([
        ['cid' => 1001, 'start' => '10:00', 'end' => '10:30'],
        ['cid' => 1002, 'start' => '10:30', 'end' => '11:30'],
        ['cid' => 1001, 'start' => '11:30', 'end' => '12:00'],
    ]);

    expect(intervalsFor($timeline, 'GIN'))->toBe([['cid' => 1001, 'start' => '10:00', 'end' => '12:00']]);
    expect(intervalsFor($timeline, 'SIG'))->toBe([['cid' => 1001, 'start' => '10:00', 'end' => '12:00']]);
});

it('attributes an airport to its local ADC when one is online', function () {
    $date = CarbonImmutable::now()->subDays(3)->toDateString();
    seedSession(2001, 'EDDF_TWR', "{$date}T08:00:00Z", "{$date}T09:00:00Z");
    seedSession(2002, 'EDDF_S_APP', "{$date}T08:00:00Z", "{$date}T10:00:00Z");

    $timeline = timelineForDay($date);

    $eddf = collect($timeline)->filter(fn ($i) => $i->key === 'airport:EDDF')
        ->map(fn ($i) => ['cid' => $i->cid, 'uid' => $i->ownerUid, 'start' => $i->start->format('H:i'), 'end' => $i->end->format('H:i')])
        ->sortBy('start')->values()->all();

    expect($eddf)->toBe([
        ['cid' => 2001, 'uid' => 'local', 'start' => '08:00', 'end' => '09:00'],
        ['cid' => 2002, 'uid' => 'DFAS', 'start' => '09:00', 'end' => '10:00'],
    ]);
});

it('caches raw sessions per day and does not refetch', function () {
    $date = CarbonImmutable::now()->subDays(3)->toDateString();
    seedSession(1001, 'EDGG_GIN_CTR', "{$date}T10:00:00Z", "{$date}T11:00:00Z");

    timelineForDay($date);

    expect(Cache::has("activity:sessions-raw:{$date}"))->toBeTrue();
});
