<?php

namespace App\Domain\Activity\Timeline;

use App\Domain\Activity\Data\OwnershipInterval;
use App\Domain\Activity\Data\StationCallsign;
use App\Domain\Activity\Reference\ActivityDataMirror;
use App\Domain\Activity\Reference\ReferenceDataRepository;
use App\Domain\Activity\Reference\SectorOwnershipIndex;
use App\Domain\Activity\Resolver\CallsignResolver;
use App\Integrations\VatsimGermanyStats\VatsimGermanyStatsClientInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Reconstructs exact ATC ownership over time by sweeping every session in a
 * window, fetched on demand from GET /api/atc/sessions.
 *
 * At each instant a sector is owned by the highest-priority position in its
 * `owner` list that has a controller online; if none, by a group bandbox login;
 * otherwise it is uncontrolled. Airports resolve to their local ADC when one is
 * online, else down their top-down chain.
 *
 * Caching is two-layered and version-aware:
 *   activity:sessions-raw:{date}          raw API rows overlapping a UTC day
 *                                         (immutable once archived -> long TTL)
 *   activity:timeline:{date}:{version}    resolved + swept ownership intervals
 */
class OwnershipTimelineBuilder
{
    private const LOCAL_SUFFIXES = ['TWR', 'GND', 'DEL', 'AFIS'];

    private const MAX_SESSION_DAYS = 32;

    public function __construct(
        private readonly ReferenceDataRepository $reference,
        private readonly SectorOwnershipIndex $ownership,
        private readonly ActivityDataMirror $mirror,
        private readonly VatsimGermanyStatsClientInterface $stats,
        private readonly CallsignResolver $resolver,
    ) {}

    /**
     * @return list<OwnershipInterval>
     */
    public function build(CarbonInterface $from, CarbonInterface $to): array
    {
        $from = CarbonImmutable::parse($from);
        $to = CarbonImmutable::parse($to);

        $historyFloor = CarbonImmutable::parse((string) config('activity.sources.sessions.history_since'))->startOfDay();
        if ($from->lessThan($historyFloor)) {
            $from = $historyFloor;
        }

        if ($from->greaterThanOrEqualTo($to)) {
            return [];
        }

        $days = [];
        for ($day = $from->startOfDay(); $day->lessThan($to); $day = $day->addDay()) {
            $days[] = $day;
        }

        $this->ensureRawDays($days);

        $out = [];

        foreach ($days as $day) {
            $sliceFrom = $day->greaterThan($from) ? $day : $from;
            $sliceTo = $day->addDay()->lessThan($to) ? $day->addDay() : $to;

            foreach ($this->buildDay($day) as $interval) {
                $start = $interval->start->greaterThan($sliceFrom) ? $interval->start : $sliceFrom;
                $end = $interval->end->lessThan($sliceTo) ? $interval->end : $sliceTo;

                if ($start->lessThan($end)) {
                    $out[] = new OwnershipInterval($interval->key, $interval->cid, $interval->ownerUid, $start, $end);
                }
            }
        }

        return $out;
    }

    /**
     * @return list<OwnershipInterval>
     */
    public function buildDay(CarbonInterface $date): array
    {
        $day = CarbonImmutable::parse($date)->startOfDay();
        $this->ensureRawDays([$day]);

        $key = "activity:timeline:{$day->toDateString()}:{$this->mirror->version()}";

        $payload = Cache::remember(
            $key,
            $this->dayTtl($day),
            fn (): array => $this->computeDay($this->rawDay($day), $day, $day->addDay()),
        );

        return array_map(
            static fn (array $r): OwnershipInterval => new OwnershipInterval(
                $r['key'], $r['cid'], $r['uid'], CarbonImmutable::parse($r['start']), CarbonImmutable::parse($r['end']),
            ),
            $payload,
        );
    }

    /**
     * Populate the raw-session cache for any of the given days that is missing,
     * with as few range requests as possible (one per contiguous run of days).
     *
     * @param  list<CarbonImmutable>  $days
     */
    private function ensureRawDays(array $days): void
    {
        $missing = array_values(array_filter(
            $days,
            fn (CarbonImmutable $d): bool => ! Cache::has($this->rawKey($d)),
        ));

        if ($missing === []) {
            return;
        }

        usort($missing, static fn (CarbonImmutable $a, CarbonImmutable $b): int => $a <=> $b);

        foreach ($this->contiguousRuns($missing) as [$runStart, $runEndExclusive]) {
            $rows = $this->stats->getSessionsInRange(
                $runStart,
                $runEndExclusive,
                (array) config('activity.sources.sessions.callsign_prefixes', []),
            );

            $buckets = [];
            for ($d = $runStart; $d->lessThan($runEndExclusive); $d = $d->addDay()) {
                $buckets[$d->toDateString()] = [];
            }

            foreach ($rows as $row) {
                foreach ($this->datesTouched($row, $runStart, $runEndExclusive) as $date) {
                    $buckets[$date][] = $row;
                }
            }

            foreach ($buckets as $date => $bucket) {
                $day = CarbonImmutable::parse($date);
                Cache::put($this->rawKey($day), $bucket, $this->dayTtl($day));
            }
        }
    }

    /**
     * @param  list<CarbonImmutable>  $sortedDays
     * @return list<array{0:CarbonImmutable,1:CarbonImmutable}> [runStart, runEndExclusive]
     */
    private function contiguousRuns(array $sortedDays): array
    {
        $runs = [];
        $start = $sortedDays[0];
        $prev = $sortedDays[0];

        foreach (array_slice($sortedDays, 1) as $day) {
            if ($day->equalTo($prev->addDay())) {
                $prev = $day;

                continue;
            }

            $runs[] = [$start, $prev->addDay()];
            $start = $day;
            $prev = $day;
        }

        $runs[] = [$start, $prev->addDay()];

        return $runs;
    }

    /**
     * @param  array<string,mixed>  $row
     * @return list<string> UTC date strings the session overlaps within the run
     */
    private function datesTouched(array $row, CarbonImmutable $runStart, CarbonImmutable $runEnd): array
    {
        if (! isset($row['connected_at'])) {
            return [];
        }

        $start = CarbonImmutable::parse($row['connected_at']);
        $end = isset($row['disconnected_at']) && $row['disconnected_at'] !== null
            ? CarbonImmutable::parse($row['disconnected_at'])
            : $runEnd;

        if ($start->lessThan($runStart)) {
            $start = $runStart;
        }
        if ($end->greaterThan($runEnd)) {
            $end = $runEnd;
        }
        if ($start->greaterThanOrEqualTo($end)) {
            return [];
        }

        $dates = [];
        $cursor = $start->startOfDay();
        $guard = 0;

        while ($cursor->lessThan($end) && $guard++ < self::MAX_SESSION_DAYS) {
            $dates[] = $cursor->toDateString();
            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    private function rawDay(CarbonImmutable $day): array
    {
        return Cache::get($this->rawKey($day)) ?? [];
    }

    private function rawKey(CarbonImmutable $day): string
    {
        return "activity:sessions-raw:{$day->toDateString()}";
    }

    private function dayTtl(CarbonImmutable $day): CarbonImmutable
    {
        $volatileDays = (int) config('activity.sources.sessions.volatile_days', 2);

        return $day->lessThan(CarbonImmutable::now()->subDays($volatileDays))
            ? CarbonImmutable::now()->addDays(60)
            : CarbonImmutable::now()->addMinutes(30);
    }

    /**
     * @param  list<array<string,mixed>>  $rawRows
     * @return list<array{key:string,cid:int,uid:string,start:string,end:string}>
     */
    private function computeDay(array $rawRows, CarbonImmutable $from, CarbonImmutable $to): array
    {
        /** @var list<array{cid:int,uid:?string,group:?string,icao:?string,start:CarbonImmutable,end:CarbonImmutable}> $spans */
        $spans = [];
        $boundaries = [$from->getTimestamp() => $from, $to->getTimestamp() => $to];

        foreach ($rawRows as $row) {
            $span = $this->toSpan($row, $from, $to);

            if ($span === null) {
                continue;
            }

            $spans[] = $span;
            $boundaries[$span['start']->getTimestamp()] = $span['start'];
            $boundaries[$span['end']->getTimestamp()] = $span['end'];
        }

        if ($spans === []) {
            return [];
        }

        ksort($boundaries);
        $points = array_values($boundaries);

        $sectorKeys = array_keys($this->ownership->allOwners());
        $airportIcaos = array_keys($this->reference->airports());

        /** @var array<string,array{cid:int,uid:string,start:CarbonImmutable,end:CarbonImmutable}> $open */
        $open = [];
        $result = [];

        for ($i = 0, $n = count($points) - 1; $i < $n; $i++) {
            $segStart = $points[$i];
            $segEnd = $points[$i + 1];

            if ($segStart->greaterThanOrEqualTo($segEnd)) {
                continue;
            }

            $active = array_values(array_filter(
                $spans,
                static fn (array $s): bool => $s['start']->lessThan($segEnd) && $s['end']->greaterThan($segStart),
            ));

            $winners = $this->resolveSegment($active, $sectorKeys, $airportIcaos);

            foreach ($open as $slot => $interval) {
                if (isset($winners[$slot]) && $this->sameWinner($winners[$slot], $interval)) {
                    $open[$slot]['end'] = $segEnd;
                } else {
                    $result[] = $this->emit($slot, $interval);
                    unset($open[$slot]);
                }
            }

            foreach ($winners as $slot => $winner) {
                if (! isset($open[$slot])) {
                    $open[$slot] = ['cid' => $winner['cid'], 'uid' => $winner['uid'], 'start' => $segStart, 'end' => $segEnd];
                }
            }
        }

        foreach ($open as $slot => $interval) {
            $result[] = $this->emit($slot, $interval);
        }

        return $result;
    }

    /**
     * Resolve one API row to a normalised span, or null if it cannot contribute.
     *
     * @param  array<string,mixed>  $row
     * @return array{cid:int,uid:?string,group:?string,icao:?string,start:CarbonImmutable,end:CarbonImmutable}|null
     */
    private function toSpan(array $row, CarbonImmutable $from, CarbonImmutable $to): ?array
    {
        if ((int) round((float) ($row['minutes_online'] ?? 0)) <= 0 || ! isset($row['connected_at'])) {
            return null;
        }

        $start = CarbonImmutable::parse($row['connected_at']);
        $end = isset($row['disconnected_at']) && $row['disconnected_at'] !== null
            ? CarbonImmutable::parse($row['disconnected_at'])
            : $to;

        if ($start->lessThan($from)) {
            $start = $from;
        }
        if ($end->greaterThan($to)) {
            $end = $to;
        }
        if ($start->greaterThanOrEqualTo($end)) {
            return null;
        }

        $callsign = strtoupper((string) ($row['callsign'] ?? ''));
        $cs = StationCallsign::parse($callsign);
        $icao = ($cs->icao() !== null && in_array($cs->suffix, self::LOCAL_SUFFIXES, true)) ? $cs->icao() : null;

        $frequencyKhz = isset($row['frequency']) && $row['frequency'] !== null
            ? (int) round(((float) $row['frequency']) * 1000)
            : null;
        $facilityType = isset($row['facility_type']) ? (int) $row['facility_type'] : null;

        $resolution = $this->resolver->resolve($callsign, $frequencyKhz, $facilityType);

        if ($resolution->positionUid === null && $resolution->groupId === null && $icao === null) {
            return null;
        }

        return [
            'cid' => (int) ($row['account_id'] ?? 0),
            'uid' => $resolution->positionUid,
            'group' => $resolution->groupId,
            'icao' => $icao,
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @param  list<array{cid:int,uid:?string,group:?string,icao:?string,start:CarbonImmutable,end:CarbonImmutable}>  $active
     * @param  list<string>  $sectorKeys
     * @param  list<string>  $airportIcaos
     * @return array<string,array{cid:int,uid:string}>
     */
    private function resolveSegment(array $active, array $sectorKeys, array $airportIcaos): array
    {
        $onlineByUid = [];
        $onlineByGroup = [];
        $localByIcao = [];

        foreach ($active as $span) {
            if ($span['uid'] !== null) {
                $onlineByUid[$span['uid']] ??= $span['cid'];
            }
            if ($span['group'] !== null) {
                $onlineByGroup[$span['group']] ??= $span['cid'];
            }
            if ($span['icao'] !== null) {
                $localByIcao[$span['icao']] ??= $span['cid'];
            }
        }

        $winners = [];

        foreach ($sectorKeys as $sectorKey) {
            foreach ($this->ownership->owners($sectorKey) as $uid) {
                if (isset($onlineByUid[$uid])) {
                    $winners["sector:{$sectorKey}"] = ['cid' => $onlineByUid[$uid], 'uid' => $uid];

                    continue 2;
                }
            }

            $groupId = $this->reference->sectors()[$sectorKey]->groupId ?? null;

            if ($groupId !== null && isset($onlineByGroup[$groupId])) {
                $winners["sector:{$sectorKey}"] = ['cid' => $onlineByGroup[$groupId], 'uid' => $groupId];
            }
        }

        foreach ($airportIcaos as $icao) {
            if (isset($localByIcao[$icao])) {
                $winners["airport:{$icao}"] = ['cid' => $localByIcao[$icao], 'uid' => 'local'];

                continue;
            }

            foreach ($this->topdownChain($icao) as $uid) {
                if (isset($onlineByUid[$uid])) {
                    $winners["airport:{$icao}"] = ['cid' => $onlineByUid[$uid], 'uid' => $uid];

                    break;
                }
            }
        }

        return $winners;
    }

    /**
     * @return list<string>
     */
    private function topdownChain(string $icao): array
    {
        $chain = [];
        $seen = [];
        $stack = [strtoupper($icao)];

        while ($stack !== []) {
            $current = array_shift($stack);

            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;

            $airport = $this->reference->airports()[$current] ?? null;

            if ($airport === null) {
                continue;
            }

            foreach ($airport->topdownUids as $uid) {
                $chain[] = $uid;
            }

            if ($airport->major !== null) {
                $stack[] = strtoupper($airport->major);
            }
        }

        return array_values(array_unique($chain));
    }

    /**
     * @param  array{cid:int,uid:string}  $winner
     * @param  array{cid:int,uid:string,start:CarbonImmutable,end:CarbonImmutable}  $interval
     */
    private function sameWinner(array $winner, array $interval): bool
    {
        return $winner['cid'] === $interval['cid'] && $winner['uid'] === $interval['uid'];
    }

    /**
     * @param  array{cid:int,uid:string,start:CarbonImmutable,end:CarbonImmutable}  $interval
     * @return array{key:string,cid:int,uid:string,start:string,end:string}
     */
    private function emit(string $slot, array $interval): array
    {
        $key = str_starts_with($slot, 'sector:') ? substr($slot, 7) : $slot;

        return [
            'key' => $key,
            'cid' => $interval['cid'],
            'uid' => $interval['uid'],
            'start' => $interval['start']->toIso8601String(),
            'end' => $interval['end']->toIso8601String(),
        ];
    }
}
