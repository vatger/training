<?php

namespace App\Domain\Activity\Engine;

use App\Domain\Activity\Data\ActivityResult;
use App\Domain\Activity\Data\ActivityScope;
use App\Domain\Activity\Data\OwnershipInterval;
use Carbon\CarbonImmutable;

/**
 * Turns a controller's ownership intervals into an {@see ActivityResult} for one
 * station: minutes owning that station's area inside the rolling window, plus
 * the endorsement-style "eligible since" date.
 *
 * The engine never re-fetches; it is handed the full timeline slice by the
 * calling action so one timeline build serves every station and controller.
 */
class ActivityCalculator
{
    public function calculate(
        int $cid,
        ActivityScope $scope,
        array $allIntervals,
        ?int $windowDays = null,
        ?CarbonImmutable $now = null,
    ): ActivityResult {
        $windowDays ??= (int) config('activity.window_days');
        $now ??= CarbonImmutable::now();
        $windowStart = $now->subDays($windowDays);
        $union = (bool) config('activity.coverage.union_minutes', true);

        $matched = $this->matchingIntervals($cid, $scope, $allIntervals);

        $windowSegments = [];
        $perKey = [];
        $lastSessionAt = null;

        foreach ($matched as $interval) {
            $start = $interval->start->greaterThan($windowStart) ? $interval->start : $windowStart;
            $end = $interval->end->lessThan($now) ? $interval->end : $now;

            if ($start->greaterThanOrEqualTo($end)) {
                continue;
            }

            $minutes = $start->diffInSeconds($end) / 60;
            $perKey[$interval->key] = ($perKey[$interval->key] ?? 0) + $minutes;
            $windowSegments[] = [$start, $end];

            if ($lastSessionAt === null || $end->greaterThan($lastSessionAt)) {
                $lastSessionAt = $end;
            }
        }

        $minutes = $union
            ? $this->unionMinutes($windowSegments)
            : array_sum($perKey);

        return new ActivityResult(
            cid: $cid,
            stationLogon: $scope->stationLogon,
            windowDays: $windowDays,
            minutes: round($minutes, 2),
            lastSessionAt: $lastSessionAt,
            eligibleSince: $this->eligibleSince($matched, $windowDays, $now),
            breakdown: [
                'mode' => $union ? 'union' : 'sum',
                'per_key' => array_map(static fn (float $m): float => round($m, 2), $perKey),
                'matched_intervals' => count($matched),
                'primary_sectors' => $scope->primarySectorKeys,
                'airport' => $scope->airportIcao,
            ],
        );
    }

    /**
     * @param  list<OwnershipInterval>  $intervals
     * @return list<OwnershipInterval>
     */
    private function matchingIntervals(int $cid, ActivityScope $scope, array $intervals): array
    {
        $sectorKeys = array_flip($scope->primarySectorKeys);
        $airportKey = $scope->airportIcao !== null ? "airport:{$scope->airportIcao}" : null;

        return array_values(array_filter($intervals, static function (OwnershipInterval $i) use ($cid, $sectorKeys, $airportKey): bool {
            if ($i->cid !== $cid) {
                return false;
            }

            return isset($sectorKeys[$i->key]) || ($airportKey !== null && $i->key === $airportKey);
        }));
    }

    /**
     * Total wall-clock minutes covered by a set of (possibly overlapping) segments.
     *
     * @param  list<array{0:CarbonImmutable,1:CarbonImmutable}>  $segments
     */
    private function unionMinutes(array $segments): float
    {
        if ($segments === []) {
            return 0.0;
        }

        usort($segments, static fn (array $a, array $b): int => $a[0]->getTimestamp() <=> $b[0]->getTimestamp());

        $total = 0.0;
        [$curStart, $curEnd] = $segments[0];

        foreach (array_slice($segments, 1) as [$start, $end]) {
            if ($start->lessThanOrEqualTo($curEnd)) {
                if ($end->greaterThan($curEnd)) {
                    $curEnd = $end;
                }
            } else {
                $total += $curStart->diffInSeconds($curEnd) / 60;
                [$curStart, $curEnd] = [$start, $end];
            }
        }

        return $total + $curStart->diffInSeconds($curEnd) / 60;
    }

    /**
     * Sliding-window "eligible since": the last time the rolling total dropped
     * below the required minutes and never climbed back. Ported from the legacy
     * VatsimActivityService, driven by ownership intervals instead of sessions.
     *
     * @param  list<OwnershipInterval>  $matched
     */
    private function eligibleSince(array $matched, int $windowDays, CarbonImmutable $now): ?CarbonImmutable
    {
        if ($matched === []) {
            return null;
        }

        $required = (int) config('activity.min_minutes');
        $events = [];

        foreach ($matched as $interval) {
            $minutes = $interval->minutes();

            if ($minutes <= 0) {
                continue;
            }

            $events[] = ['at' => $interval->end, 'delta' => $minutes];
            $events[] = ['at' => $interval->end->addDays($windowDays), 'delta' => -$minutes];
        }

        usort($events, static fn (array $a, array $b): int => $a['at']->getTimestamp() <=> $b['at']->getTimestamp());

        $running = 0.0;
        $eligibleSince = null;

        foreach ($events as $event) {
            if ($event['at']->greaterThan($now)) {
                break;
            }

            $before = $running;
            $running += $event['delta'];

            if ($before >= $required && $running < $required) {
                $eligibleSince = $event['at'];
            }

            if ($running >= $required) {
                $eligibleSince = null;
            }
        }

        return $running < $required ? $eligibleSince : null;
    }
}
