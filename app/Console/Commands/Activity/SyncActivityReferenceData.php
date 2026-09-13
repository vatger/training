<?php

namespace App\Console\Commands\Activity;

use App\Domain\Activity\Actions\RebuildActivityReferenceData;
use App\Domain\Activity\Reference\ActivityDataMirror;
use App\Jobs\Activity\SyncActivityReferenceData as SyncActivityReferenceDataJob;
use Illuminate\Console\Command;

class SyncActivityReferenceData extends Command
{
    protected $signature = 'activity:sync-reference
        {--queue : Dispatch the sync job onto the queue instead of running inline}';

    protected $description = 'Mirror VATGLASSES + datahub reference data and rebuild activity caches';

    public function handle(ActivityDataMirror $mirror, RebuildActivityReferenceData $rebuild): int
    {
        if ($this->option('queue')) {
            SyncActivityReferenceDataJob::dispatch();
            $this->info('Dispatched activity reference sync job.');

            return self::SUCCESS;
        }

        $this->info('Fetching VATGLASSES + datahub reference data...');

        try {
            $result = $mirror->sync();
        } catch (\Throwable $e) {
            $this->error('Reference sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line("Mirrored {$result['files']} file(s). Version {$result['version']}.");

        if ($result['changed']) {
            $this->info('Data changed - rebuilding caches...');
            $rebuild->execute();
        } else {
            $this->line('No change since last sync.');
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
