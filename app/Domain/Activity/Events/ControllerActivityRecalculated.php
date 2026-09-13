<?php

namespace App\Domain\Activity\Events;

readonly class ControllerActivityRecalculated
{
    /**
     * @param  list<string>  $stationLogons
     */
    public function __construct(
        public int $cid,
        public array $stationLogons,
        public int $windowDays,
    ) {}
}
