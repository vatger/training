<?php

namespace App\Console\Commands;

use App\Domain\WaitingList\Actions\ProcessMonthlyWaitingListVerification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessWaitingListVerification extends Command
{
    protected $signature = 'waitinglists:verify-interest';

    protected $description = 'Monthly waiting list interest verification: purge unconfirmed entries and request reconfirmation';

    public function __construct(
        private readonly ProcessMonthlyWaitingListVerification $verification,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting waiting list interest verification...');

        try {
            $this->verification->execute();

            $this->info('Waiting list interest verification completed successfully.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error during waiting list interest verification: '.$e->getMessage());
            Log::error('Waiting list interest verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
