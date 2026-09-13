<?php

namespace App\Domain\Activity\Actions;

use App\Domain\Activity\Data\ActivityResult;

/**
 * Convenience wrapper: calculate + persist activity for one controller on one
 * station. Delegates to {@see CalculateControllerActivity} so the timeline build
 * and caching are shared.
 */
class CalculateStationActivity
{
    public function __construct(
        private readonly CalculateControllerActivity $controller,
    ) {}

    public function execute(int $cid, string $stationLogon, ?int $windowDays = null): ?ActivityResult
    {
        $results = $this->controller->execute($cid, [$stationLogon], $windowDays);

        return $results[strtoupper($stationLogon)] ?? null;
    }
}
