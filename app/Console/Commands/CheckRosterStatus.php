<?php

namespace App\Console\Commands;

use App\Domain\Roster\Actions\CheckUserRosterStatus;
use App\Integrations\VatEud\VatEudClientInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckRosterStatus extends Command
{
    protected $signature = 'roster:check';

    protected $description = 'Check roster status and remove inactive users';

    public function __construct(
        private readonly VatEudClientInterface $vatEudClient,
        private readonly CheckUserRosterStatus $checkUserRosterStatus,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting roster check...');

        try {
            $roster = $this->vatEudClient->getRoster();

            if (empty($roster)) {
                $this->error('Failed to fetch roster from VatEUD');

                return 1;
            }

            $this->info('Found '.count($roster).' users on roster');

            foreach ($roster as $vatsimId) {
                try {
                    $this->checkUserRosterStatus->execute($vatsimId);
                } catch (\Throwable $e) {
                    Log::error('ROSTER CHECK FAILED', [
                        'vatsim_id' => $vatsimId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info('Roster check completed successfully.');

            return 0;

        } catch (\Exception $e) {
            $this->error('Error during roster check: '.$e->getMessage());
            Log::error('Roster check error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return 1;
        }
    }
}
