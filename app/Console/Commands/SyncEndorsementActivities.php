<?php

namespace App\Console\Commands;

use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\EndorsementActivity;
use App\Services\VatsimActivityService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncEndorsementActivities extends Command
{
    protected $signature = 'endorsements:sync-activities';
    protected $description = 'Sync endorsement activities from VATSIM and VatEUD for all endorsements';

    public function __construct(
        private readonly VatEudClientInterface $vatEudClient,
        private readonly VatsimActivityService $activityService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting endorsement activity sync for ALL endorsements...');

        try {
            $this->syncAllTier1Endorsements();
            $this->updateAllActivities();

            $this->info('Endorsement activity sync completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error during sync: ' . $e->getMessage());
            Log::error('Endorsement sync error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }
    }

    private function syncAllTier1Endorsements(): void
    {
        $this->info('Fetching ALL Tier 1 endorsements from VatEUD...');

        $tier1Endorsements = $this->vatEudClient->getTier1Endorsements();

        $this->info('Found ' . count($tier1Endorsements) . ' Tier 1 endorsements');

        foreach ($tier1Endorsements as $endorsement) {
            try {
                if (EndorsementActivity::where('endorsement_id', $endorsement->id)->exists()) {
                    continue;
                }

                $createdAt = $endorsement->createdAt;

                EndorsementActivity::create([
                    'endorsement_id' => $endorsement->id,
                    'vatsim_id' => $endorsement->userCid,
                    'position' => $endorsement->position,
                    'activity_minutes' => 0.0,
                    'created_at_vateud' => $createdAt ?? Carbon::createFromTimestamp(1),
                    'last_updated' => Carbon::createFromTimestamp(1),
                ]);

                $this->line("Created activity record for endorsement {$endorsement->id} (User: {$endorsement->userCid}, Position: {$endorsement->position})");
            } catch (\Exception $e) {
                $this->error("Failed to sync endorsement {$endorsement->id}: " . $e->getMessage());
                Log::error('Failed to sync endorsement', ['endorsement_id' => $endorsement->id, 'error' => $e->getMessage()]);
            }
        }

        $this->cleanupRemovedEndorsements($tier1Endorsements);
    }

    private function cleanupRemovedEndorsements(array $currentEndorsements): void
    {
        $currentIds = collect($currentEndorsements)->pluck('id')->toArray();
        $removedCount = EndorsementActivity::whereNotIn('endorsement_id', $currentIds)->delete();

        if ($removedCount > 0) {
            $this->info("Cleaned up {$removedCount} removed Tier 1 endorsements");
        }
    }

    private function updateAllActivities(): void
    {
        $totalCount = EndorsementActivity::count();

        if ($totalCount === 0) {
            $this->info('No endorsements to update');
            return;
        }

        $this->info("Updating activity for {$totalCount} endorsement(s)...");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $processedCount = 0;

        EndorsementActivity::each(function (EndorsementActivity $endorsementActivity) use (&$processedCount, $bar) {
            $this->updateEndorsementActivity($endorsementActivity);
            $processedCount++;
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Completed updating {$processedCount} endorsements.");
    }

    private function updateEndorsementActivity(EndorsementActivity $endorsementActivity): void
    {
        try {
            $activityResult = $this->activityService->getEndorsementActivity([
                'user_cid' => $endorsementActivity->vatsim_id,
                'position' => $endorsementActivity->position,
            ]);

            $activityMinutes = $activityResult['minutes'] ?? 0;
            $minRequiredMinutes = config('services.vateud.min_activity_minutes', 180);

            $endorsementActivity->activity_minutes = $activityMinutes;
            $endorsementActivity->last_activity_date = $activityResult['last_activity_date'] ?? null;
            $endorsementActivity->last_updated = now();

            if ($activityMinutes >= $minRequiredMinutes && $endorsementActivity->removal_date) {
                $endorsementActivity->removal_date = null;
                $endorsementActivity->removal_notified = false;
            }

            $endorsementActivity->eligible_since = $this->activityService->calculateEligibleSince([
                'user_cid' => $endorsementActivity->vatsim_id,
                'position' => $endorsementActivity->position,
            ]);

            $endorsementActivity->save();
        } catch (\Exception $e) {
            Log::error('Failed to update endorsement activity', [
                'endorsement_id' => $endorsementActivity->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}