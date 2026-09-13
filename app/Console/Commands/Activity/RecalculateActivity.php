<?php

namespace App\Console\Commands\Activity;

use App\Domain\Activity\Actions\CalculateControllerActivity;
use App\Jobs\Activity\RecalculateActivityBatch;
use Illuminate\Console\Command;

class RecalculateActivity extends Command
{
    protected $signature = 'activity:recalculate
        {--cid=* : One or more CIDs to recalculate}
        {--station=* : Restrict to these station logons (implies inline)}
        {--window= : Override the rolling window in days}
        {--from-roster : Recalculate every CID on the roster}
        {--from-endorsements : Recalculate every CID with a tracked endorsement}
        {--sync : Run inline instead of dispatching a queued batch}';

    protected $description = 'Recalculate controller activity for every station from the ownership timeline';

    public function handle(CalculateControllerActivity $calculate): int
    {
        $cids = array_values(array_filter(array_map('intval', (array) $this->option('cid'))));
        $stations = array_map('strtoupper', (array) $this->option('station'));
        $window = $this->option('window') !== null ? (int) $this->option('window') : null;
        $inline = (bool) $this->option('sync') || $stations !== [];

        if ($inline) {
            if ($cids === []) {
                $this->error('Inline mode needs at least one --cid.');

                return self::FAILURE;
            }

            foreach ($cids as $cid) {
                $results = $calculate->execute($cid, $stations, $window);
                $this->line("CID {$cid}: ".count($results).' station(s)');

                foreach ($results as $logon => $result) {
                    $this->line(sprintf(
                        '  %-16s %6.1f min  last %s  eligible-since %s',
                        $logon,
                        $result->minutes,
                        $result->lastSessionAt?->toDateString() ?? '-',
                        $result->eligibleSince?->toDateString() ?? '-',
                    ));
                }
            }

            return self::SUCCESS;
        }

        RecalculateActivityBatch::dispatch(
            $cids,
            (bool) $this->option('from-roster'),
            (bool) $this->option('from-endorsements'),
            $window,
        );

        $this->info('Dispatched activity recalculation batch.');

        return self::SUCCESS;
    }
}
