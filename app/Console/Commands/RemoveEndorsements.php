<?php

namespace App\Console\Commands;

use App\Integrations\VatEud\VatEudClientInterface;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\EndorsementActivity;
use App\Models\User;
use App\Services\VatsimActivityService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveEndorsements extends Command
{
    protected $signature = 'endorsements:remove';
    protected $description = 'Remove endorsements that have passed their removal date and send pending notifications';

    public function __construct(
        private readonly VatEudClientInterface $vatEudClient,
        private readonly VatgerClientInterface $vatgerClient,
        private readonly VatsimActivityService $activityService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting endorsement removal process...');

        try {
            $this->sendRemovalNotifications();
            $this->processRemovals();

            $this->info('Endorsement removal process completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error during removal process: ' . $e->getMessage());
            Log::error('Endorsement removal error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }
    }

    private function sendRemovalNotifications(): void
    {
        $pending = EndorsementActivity::whereNotNull('removal_date')
            ->where('removal_notified', false)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending removal notifications to send.');
            return;
        }

        $this->info("Found {$pending->count()} endorsement(s) needing removal notification...");

        foreach ($pending as $endorsement) {
            try {
                $this->sendNotification($endorsement);

                $endorsement->removal_notified = true;
                $endorsement->save();

                $this->info("✓ Sent notification for {$endorsement->position} (ID: {$endorsement->endorsement_id})");
            } catch (\Exception $e) {
                $this->error("✗ Failed to send notification for endorsement {$endorsement->id}: " . $e->getMessage());
                Log::error('Failed to send removal notification', [
                    'endorsement_id' => $endorsement->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function processRemovals(): void
    {
        $toRemove = EndorsementActivity::whereNotNull('removal_date')
            ->where('removal_date', '<', Carbon::now())
            ->where('removal_notified', true)
            ->get();

        if ($toRemove->isEmpty()) {
            $this->info('No endorsements ready for removal.');
            return;
        }

        $this->info("Found {$toRemove->count()} endorsement(s) ready for removal...");

        foreach ($toRemove as $endorsement) {
            try {
                $tier1Endorsements = $this->vatEudClient->getTier1Endorsements();
                $tier1Entry = collect($tier1Endorsements)->firstWhere('id', $endorsement->endorsement_id);

                if (!$tier1Entry) {
                    $this->warn("Endorsement {$endorsement->endorsement_id} not found in VatEUD, removing local record");
                    $endorsement->delete();
                    continue;
                }

                $activityData = $this->activityService->getEndorsementActivity([
                    'user_cid' => $tier1Entry->userCid,
                    'position' => $tier1Entry->position,
                ]);
                $currentActivityMinutes = $activityData['minutes'];
                $minMinutes = config('services.vateud.min_activity_minutes', 180);

                if ($currentActivityMinutes >= $minMinutes) {
                    $this->info("Endorsement {$endorsement->endorsement_id} now has sufficient activity, cancelling removal");
                    $endorsement->activity_minutes = $currentActivityMinutes;
                    if ($activityData['last_activity_date']) {
                        $endorsement->last_activity_date = $activityData['last_activity_date'];
                    }
                    $endorsement->removal_date = null;
                    $endorsement->removal_notified = false;
                    $endorsement->save();
                    continue;
                }

                $success = $this->vatEudClient->deleteTier1Endorsement($endorsement->endorsement_id);

                if ($success) {
                    $this->info("✓ Removed endorsement {$endorsement->endorsement_id} ({$endorsement->position}) for user {$endorsement->vatsim_id}");
                    event(new \App\Domain\Endorsement\Events\EndorsementRemoved(
                        activity: $endorsement,
                        activityMinutes: $currentActivityMinutes,
                    ));
                    $endorsement->delete();
                } else {
                    $this->error("✗ Failed to remove endorsement {$endorsement->endorsement_id} via VatEUD API");
                }
            } catch (\Exception $e) {
                $this->error("Error processing endorsement {$endorsement->id}: " . $e->getMessage());
                Log::error('Endorsement removal error', ['endorsement_id' => $endorsement->id, 'error' => $e->getMessage()]);
            }
        }
    }

    private function sendNotification(EndorsementActivity $endorsement): void
    {
        $message = sprintf(
            'Your endorsement for %s will be removed on %s. If you wish to keep it, please ensure you meet the minimum activity requirements by then.',
            $endorsement->position,
            $endorsement->removal_date->format('d.m.Y'),
        );

        $result = $this->vatgerClient->sendNotification(
            vatsimId: $endorsement->vatsim_id,
            title: 'Endorsement Removal',
            message: $message,
            sourceName: 'VATGER ATD',
            linkUrl: 'https://training.vatsim-germany.org',
        );

        if (!$result['success']) {
            throw new \RuntimeException("Failed to send notification to {$endorsement->vatsim_id}");
        }
    }
}