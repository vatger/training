<?php

namespace App\Domain\Activity\Data;

use Carbon\CarbonImmutable;

/**
 * The computed activity of one controller on one station over a rolling window.
 *
 * `eligibleSince` mirrors the endorsement retention semantics: the moment the
 * controller's rolling total last fell below the required minimum and never
 * recovered (null while still active or never active).
 */
readonly class ActivityResult
{
    /**
     * @param  array<string,mixed>  $breakdown
     */
    public function __construct(
        public int $cid,
        public string $stationLogon,
        public int $windowDays,
        public float $minutes,
        public ?CarbonImmutable $lastSessionAt,
        public ?CarbonImmutable $eligibleSince,
        public array $breakdown = [],
    ) {}

    public function hours(): float
    {
        return round($this->minutes / 60, 2);
    }

    public function isActive(): bool
    {
        return $this->minutes >= (int) config('activity.min_minutes');
    }
}
