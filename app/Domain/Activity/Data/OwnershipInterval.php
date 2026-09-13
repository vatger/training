<?php

namespace App\Domain\Activity\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * A contiguous span during which one controller was the resolved owner of one
 * sector (or airport, keyed "airport:{ICAO}").
 *
 * `ownerUid` is the winning VATGLASSES position uid, the group id for a bandbox
 * owner, or "local" when an aerodrome station held its own field.
 */
readonly class OwnershipInterval
{
    public CarbonImmutable $start;

    public CarbonImmutable $end;

    public function __construct(
        public string $key,
        public int $cid,
        public string $ownerUid,
        CarbonInterface $start,
        CarbonInterface $end,
    ) {
        $this->start = CarbonImmutable::parse($start);
        $this->end = CarbonImmutable::parse($end);
    }

    public function minutes(): float
    {
        return max(0.0, $this->start->diffInSeconds($this->end) / 60);
    }

    public function isAirport(): bool
    {
        return str_starts_with($this->key, 'airport:');
    }
}
