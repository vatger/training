<?php

namespace App\Domain\Activity\Actions;

use App\Domain\Activity\Events\ActivityReferenceDataUpdated;
use App\Domain\Activity\Reference\ActivityDataMirror;
use App\Domain\Activity\Reference\CallsignResolutionMap;
use App\Domain\Activity\Reference\ReferenceDataRepository;
use App\Domain\Activity\Reference\SectorOwnershipIndex;
use App\Domain\Activity\Reference\StationRegistry;
use Illuminate\Support\Facades\Log;

/**
 * Re-parses the mirrored reference data into the version-stamped caches after a
 * data change, then announces the new version.
 */
class RebuildActivityReferenceData
{
    public function __construct(
        private readonly ActivityDataMirror $mirror,
        private readonly ReferenceDataRepository $reference,
        private readonly StationRegistry $stations,
        private readonly SectorOwnershipIndex $ownership,
        private readonly CallsignResolutionMap $resolverMap,
    ) {}

    public function execute(): string
    {
        $version = $this->mirror->version();

        $this->reference->flush();
        $this->stations->flush();
        $this->ownership->flush();
        $this->resolverMap->flush();

        // Warm the caches so the first real request is not slow, and so a data
        // problem (e.g. ambiguous callsigns) surfaces here rather than mid-job.
        $positions = $this->reference->positions();
        $sectors = $this->reference->sectors();
        $airports = $this->reference->airports();
        $stations = $this->stations->all();
        $this->ownership->groupSectorKeys('__warm__');
        $collisions = $this->resolverMap->collisions();

        Log::info('Activity reference data rebuilt', [
            'version' => $version,
            'positions' => count($positions),
            'sectors' => count($sectors),
            'airports' => count($airports),
            'stations' => count($stations),
            'callsign_collisions' => count($collisions),
        ]);

        if ($collisions !== []) {
            Log::warning('Activity: ambiguous callsign normalisations detected', ['keys' => $collisions]);
        }

        event(new ActivityReferenceDataUpdated($version, count($stations)));

        return $version;
    }
}
