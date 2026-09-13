<?php

namespace App\Jobs\Activity;

use App\Domain\Activity\Actions\CalculateControllerActivity;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Recalculates and persists one controller's activity across every station they
 * have flown. Sessions are pulled on demand by the ownership timeline (and
 * cached per UTC day), so there is no per-controller sync step.
 */
class RecalculateControllerActivity implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * @param  list<string>  $stationLogons
     */
    public function __construct(
        public int $cid,
        public array $stationLogons = [],
        public ?int $windowDays = null,
    ) {
        $this->onQueue(config('activity.queue'));
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("activity-recalc-{$this->cid}"))->expireAfter(600)];
    }

    public function handle(CalculateControllerActivity $calculate): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $calculate->execute($this->cid, $this->stationLogons, $this->windowDays);
    }
}
