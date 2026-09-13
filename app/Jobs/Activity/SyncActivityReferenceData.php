<?php

namespace App\Jobs\Activity;

use App\Domain\Activity\Actions\RebuildActivityReferenceData;
use App\Domain\Activity\Reference\ActivityDataMirror;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Refreshes the on-disk mirror of the VATGLASSES + datahub reference data and,
 * when the content changed, rebuilds the version-stamped caches.
 */
class SyncActivityReferenceData implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public int $uniqueFor = 1800;

    public function __construct()
    {
        $this->onQueue(config('activity.queue'));
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ActivityDataMirror $mirror, RebuildActivityReferenceData $rebuild): void
    {
        $result = $mirror->sync();

        Log::info('Activity reference mirror synced', $result);

        if ($result['changed']) {
            $rebuild->execute();
        }
    }
}
