<?php

namespace App\Jobs\Activity;

use App\Models\EndorsementActivity;
use App\Models\RosterEntry;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fans a full activity recalculation out into a batch of per-controller jobs.
 */
class RecalculateActivityBatch implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    /**
     * @param  list<int>  $cids
     */
    public function __construct(
        public array $cids = [],
        public bool $fromRoster = false,
        public bool $fromEndorsements = false,
        public ?int $windowDays = null,
    ) {
        $this->onQueue(config('activity.queue'));
    }

    public function handle(): void
    {
        $cids = $this->resolveCids();

        if ($cids === []) {
            Log::warning('RecalculateActivityBatch: no CIDs resolved');

            return;
        }

        $jobs = array_map(
            fn (int $cid) => new RecalculateControllerActivity($cid, [], $this->windowDays),
            $cids,
        );

        Bus::batch($jobs)
            ->name('activity:recalculate')
            ->onQueue(config('activity.queue'))
            ->allowFailures()
            ->then(fn (Batch $batch) => Log::info('Activity recalculation batch finished', [
                'processed' => $batch->processedJobs(),
                'failed' => $batch->failedJobs,
            ]))
            ->catch(fn (Batch $batch, Throwable $e) => Log::error('Activity recalculation batch error', [
                'error' => $e->getMessage(),
            ]))
            ->dispatch();
    }

    /**
     * @return list<int>
     */
    private function resolveCids(): array
    {
        $cids = $this->cids;

        if ($this->fromRoster) {
            $cids = [...$cids, ...RosterEntry::query()->pluck('user_id')->all()];
        }

        if ($this->fromEndorsements) {
            $cids = [...$cids, ...EndorsementActivity::query()->distinct()->pluck('vatsim_id')->all()];
        }

        return array_values(array_unique(array_filter(array_map('intval', $cids))));
    }
}
