<?php

namespace App\Domain\Activity\Data;

/**
 * What counts toward a login station's activity:
 *   - `primarySectorKeys`: VATGLASSES sectors the station's position primarily
 *     owns (owner[0]); time spent as the live owner of any of these counts.
 *   - `airportIcao`: aerodrome / airport whose top-down ownership also counts
 *     (set for TWR/GND/DEL and for APP/DEP tied to an airport).
 *   - `selfMatchLogons`: exact callsigns that always count (the station itself
 *     and its aliases) - used for aerodrome stations not yet modelled in
 *     VATGLASSES.
 *   - `groupId`: set only for a group bandbox station.
 */
readonly class ActivityScope
{
    /**
     * @param  list<string>  $primarySectorKeys
     * @param  list<string>  $selfMatchLogons
     */
    public function __construct(
        public string $stationLogon,
        public ?string $positionUid,
        public array $primarySectorKeys,
        public ?string $airportIcao,
        public array $selfMatchLogons,
        public ?string $groupId = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->primarySectorKeys === []
            && $this->airportIcao === null
            && $this->groupId === null;
    }
}
