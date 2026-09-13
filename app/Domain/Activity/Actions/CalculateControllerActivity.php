<?php

namespace App\Domain\Activity\Actions;

use App\Domain\Activity\Data\ActivityResult;
use App\Domain\Activity\Data\OwnershipInterval;
use App\Domain\Activity\Engine\ActivityCalculator;
use App\Domain\Activity\Events\ControllerActivityRecalculated;
use App\Domain\Activity\Reference\StationRegistry;
use App\Domain\Activity\Resolver\StationActivityScope;
use App\Domain\Activity\Timeline\OwnershipTimelineBuilder;
use App\Models\ControllerActivity;
use Carbon\CarbonImmutable;

/**
 * Calculates and persists activity for one controller across many stations,
 * reusing a single ownership-timeline build (which is itself cached per day, so
 * running this for the whole roster is cheap after the first controller).
 *
 * Stations with zero minutes are not written unless explicitly requested, so
 * the table stays small and meaningful.
 */
class CalculateControllerActivity
{
    /** Extra history beyond the window so "eligible since" can be derived. */
    private const ELIGIBILITY_LOOKBACK_DAYS = 730;

    public function __construct(
        private readonly OwnershipTimelineBuilder $timeline,
        private readonly ActivityCalculator $calculator,
        private readonly StationActivityScope $scopes,
        private readonly StationRegistry $stations,
    ) {}

    /**
     * @param  list<string>  $stationLogons  empty = every station the controller has activity on
     * @return array<string,ActivityResult>
     */
    public function execute(int $cid, array $stationLogons = [], ?int $windowDays = null): array
    {
        $windowDays ??= (int) config('activity.window_days');
        $now = CarbonImmutable::now();
        $span = max($windowDays, self::ELIGIBILITY_LOOKBACK_DAYS);

        $intervals = $this->timeline->build($now->subDays($span), $now);
        $mine = array_values(array_filter($intervals, static fn ($i): bool => $i->cid === $cid));

        $explicit = array_map('strtoupper', $stationLogons);
        $targets = $explicit !== []
            ? $explicit
            : $this->stationsWithActivity($mine);

        $results = [];

        foreach ($targets as $logon) {
            $station = $this->stations->find($logon);

            if ($station === null) {
                continue;
            }

            $scope = $this->scopes->for($station);
            $result = $this->calculator->calculate($cid, $scope, $mine, $windowDays, $now);

            $forced = in_array($logon, $explicit, true);

            if ($result->minutes <= 0 && $result->lastSessionAt === null && $result->eligibleSince === null && ! $forced) {
                continue;
            }

            $this->persist($result, $now);
            $results[$logon] = $result;
        }

        $this->pruneStaleRows($cid, $windowDays, $explicit, array_keys($results));

        event(new ControllerActivityRecalculated($cid, array_keys($results), $windowDays));

        return $results;
    }

    /**
     * Every station whose primary sectors or airport the controller ever owned.
     *
     * @param  list<OwnershipInterval>  $mine
     * @return list<string>
     */
    private function stationsWithActivity(array $mine): array
    {
        $ownedKeys = [];
        foreach ($mine as $interval) {
            $ownedKeys[$interval->key] = true;
        }

        $logons = [];
        foreach ($this->stations->all() as $station) {
            $scope = $this->scopes->for($station);

            foreach ($scope->primarySectorKeys as $sectorKey) {
                if (isset($ownedKeys[$sectorKey])) {
                    $logons[$station->logon] = true;

                    continue 2;
                }
            }

            if ($scope->airportIcao !== null && isset($ownedKeys["airport:{$scope->airportIcao}"])) {
                $logons[$station->logon] = true;
            }
        }

        return array_keys($logons);
    }

    /**
     * On a full recalculation, remove rows for stations that no longer have any
     * activity. On a targeted run (explicit logons) only prune those logons.
     *
     * @param  list<string>  $explicit
     * @param  list<string>  $keptLogons
     */
    private function pruneStaleRows(int $cid, int $windowDays, array $explicit, array $keptLogons): void
    {
        $query = ControllerActivity::query()
            ->where('cid', $cid)
            ->where('window_days', $windowDays)
            ->whereNotIn('station_logon', $keptLogons);

        if ($explicit !== []) {
            $query->whereIn('station_logon', $explicit);
        }

        $query->delete();
    }

    private function persist(ActivityResult $result, CarbonImmutable $now): void
    {
        ControllerActivity::query()->updateOrCreate(
            [
                'cid' => $result->cid,
                'station_logon' => $result->stationLogon,
                'window_days' => $result->windowDays,
            ],
            [
                'minutes' => $result->minutes,
                'last_session_at' => $result->lastSessionAt,
                'eligible_since' => $result->eligibleSince,
                'breakdown' => $result->breakdown,
                'calculated_at' => $now,
            ],
        );
    }
}
