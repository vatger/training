<?php

namespace App\Domain\Activity\Resolver;

use App\Domain\Activity\Data\ActivityScope;
use App\Domain\Activity\Data\Station;
use App\Domain\Activity\Reference\ReferenceDataRepository;
use App\Domain\Activity\Reference\SectorOwnershipIndex;
use App\Domain\Activity\Reference\StationRegistry;

/**
 * Builds the {@see ActivityScope} for a login station: the set of primary-owner
 * sectors and/or the airport whose top-down ownership accrues activity for it.
 */
class StationActivityScope
{
    public function __construct(
        private readonly StationRegistry $stations,
        private readonly ReferenceDataRepository $reference,
        private readonly SectorOwnershipIndex $ownership,
    ) {}

    public function forLogon(string $logon): ?ActivityScope
    {
        $station = $this->stations->find($logon);

        return $station ? $this->for($station) : null;
    }

    public function for(Station $station): ActivityScope
    {
        $uid = $station->positionUid;
        $callsign = $station->callsign();
        $icao = $callsign->icao();

        $primarySectorKeys = $uid !== null
            ? $this->ownership->primarySectorKeysFor($uid)
            : [];

        // Aerodrome stations, and airport-tied APP/DEP, also earn activity
        // through top-down control of that airport.
        $airportIsModelled = $icao !== null && array_key_exists($icao, $this->reference->airports());
        $airportIcao = ($icao !== null && ($station->isAerodrome() || $airportIsModelled))
            ? $icao
            : null;

        $selfMatch = [$station->logon];
        foreach ((array) config('activity.callsign_aliases') as $from => $to) {
            $target = is_array($to) ? ($to['logon'] ?? null) : $to;
            if (is_string($target) && strtoupper($target) === $station->logon) {
                $selfMatch[] = strtoupper((string) $from);
            }
        }

        return new ActivityScope(
            stationLogon: $station->logon,
            positionUid: $uid,
            primarySectorKeys: $primarySectorKeys,
            airportIcao: $airportIcao,
            selfMatchLogons: array_values(array_unique($selfMatch)),
        );
    }
}
