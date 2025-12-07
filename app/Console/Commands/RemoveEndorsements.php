<?php

namespace App\Console\Commands;

use App\Models\EndorsementActivity;
use App\Services\ActivityLogger;
use App\Services\VatEudService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveEndorsements extends Command
{
    protected $signature = 'endorsements:remove {--notify : Also process notification sending}';
    protected $description = 'Remove endorsements that have passed their removal date and send pending notifications';

    protected VatEudService $vatEudService;

    public function __construct(VatEudService $vatEudService)
    {
        parent::__construct();
        $this->vatEudService = $vatEudService;
    }

    public function handle(): int
    {
        $this->info('Starting endorsement removal process...');

        try {
            // Step 1: Send notifications for endorsements marked for removal (if --notify flag)
            if ($this->option('notify')) {
                $this->sendRemovalNotifications();
            }

            // Step 2: Process actual removals
            $this->processRemovals();

            $this->info('Endorsement removal process completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error during removal process: ' . $e->getMessage());
            Log::error('Endorsement removal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Send notifications for endorsements that are marked for removal but not yet notified
     */
    protected function sendRemovalNotifications(): void
    {
        $pendingNotifications = EndorsementActivity::whereNotNull('removal_date')
            ->where('removal_notified', false)
            ->get();

        if ($pendingNotifications->isEmpty()) {
            $this->info('No pending removal notifications to send.');
            return;
        }

        $this->info("Found {$pendingNotifications->count()} endorsement(s) needing removal notification...");

        foreach ($pendingNotifications as $endorsement) {
            try {
                $this->sendNotification($endorsement);
                
                $endorsement->removal_notified = true;
                $endorsement->save();

                $this->info("✓ Sent notification for {$endorsement->position} (ID: {$endorsement->endorsement_id})");

            } catch (\Exception $e) {
                $this->error("✗ Failed to send notification for endorsement {$endorsement->id}: " . $e->getMessage());
                Log::error('Failed to send removal notification', [
                    'endorsement_id' => $endorsement->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Process actual removals for endorsements past their removal date
     */
    protected function processRemovals(): void
    {
        $now = Carbon::now();

        $endorsementsToRemove = EndorsementActivity::whereNotNull('removal_date')
            ->where('removal_date', '<', $now)
            ->where('removal_notified', true)
            ->get();

        if ($endorsementsToRemove->isEmpty()) {
            $this->info('No endorsements ready for removal.');
            return;
        }

        $this->info("Found {$endorsementsToRemove->count()} endorsement(s) ready for removal...");

        foreach ($endorsementsToRemove as $endorsement) {
            try {
                $tier1Endorsements = $this->vatEudService->getTier1Endorsements();
                $tier1Entry = collect($tier1Endorsements)->firstWhere('id', $endorsement->endorsement_id);

                if (!$tier1Entry) {
                    $this->warn("Endorsement {$endorsement->endorsement_id} not found in VatEUD, removing local record");
                    $endorsement->delete();
                    continue;
                }

                $activityService = app(\App\Services\VatsimActivityService::class);
                $activityData = $activityService->getEndorsementActivity($tier1Entry);
                $currentActivityMinutes = $activityData['minutes'];

                $this->info("Current activity for {$endorsement->position}: {$currentActivityMinutes} minutes (was: {$endorsement->activity_minutes})");

                $minMinutes = config('services.vateud.min_activity_minutes', 180);

                if ($currentActivityMinutes >= $minMinutes) {
                    $this->info("Endorsement {$endorsement->endorsement_id} now has sufficient activity ({$currentActivityMinutes} min), cancelling removal");
                    $endorsement->activity_minutes = $currentActivityMinutes;
                    if ($activityData['last_activity_date']) {
                        $endorsement->last_activity_date = $activityData['last_activity_date'];
                    }
                    $endorsement->removal_date = null;
                    $endorsement->removal_notified = false;
                    $endorsement->save();
                    continue;
                }

                $traineeId = $endorsement->trainee_id;
                $traineeName = $endorsement->trainee->name;

                $success = $this->vatEudService->removeTier1Endorsement($endorsement->endorsement_id);

                if ($success) {
                    $this->info("✓ Removed endorsement {$endorsement->endorsement_id} ({$endorsement->position}) for user {$endorsement->vatsim_id}");

                    ActivityLogger::log(
                        'endorsement.deleted',
                        $endorsement,
                        "Removed endorsement $endorsement->position for $endorsement->vatsim_id",
                        [
                            'activity_minutes' => $currentActivityMinutes,
                            'removal_date' => $endorsement->removal_date,
                            'endorsement_id' => $endorsement->endorsement_id,
                        ]
                    );

                    $endorsement->delete();
                } else {
                    $this->error("✗ Failed to remove endorsement {$endorsement->endorsement_id} via VatEUD API");
                }

            } catch (\Exception $e) {
                $this->error("Error processing endorsement {$endorsement->id}: " . $e->getMessage());
                Log::error('Endorsement removal error', [
                    'endorsement_id' => $endorsement->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Send removal notification to user
     */
    protected function sendNotification(EndorsementActivity $endorsement): void
    {
        $apiKey = config('services.vatger.api_key');
        
        if (!$apiKey) {
            Log::warning('VATGER API key not configured, skipping notification');
            return;
        }

        $message = sprintf(
            "Your endorsement for %s will be removed on %s. If you wish to keep it, please ensure you meet the minimum activity requirements by then.",
            $endorsement->position,
            $endorsement->removal_date->format('d.m.Y')
        );

        $data = [
            'title' => 'Endorsement Removal',
            'message' => $message,
            'source_name' => 'VATGER ATD',
            'link_text' => 'Training Centre',
            'link_url' => 'https://training.vatsim-germany.org',
            'via' => 'board.ping',
        ];

        $headers = [
            'Authorization' => "Token {$apiKey}",
        ];

        $subject = \App\Models\User::where('vatsim_id', $endorsement->vatsim_id)->first();


        ActivityLogger::log(
            'endorsement.notified',
            $subject,
            "{$endorsement->vatsim_id} notified removal of {$endorsement->position} endorsement",
            [
                'activity_minutes' => $endorsement->activity_minutes,
                'removal_date' => $endorsement->removal_date,
                'endorsement_id' => $endorsement->endorsement_id,
            ]
        );

        $response = \Http::withHeaders($headers)
            ->post("https://vatsim-germany.org/api/user/{$endorsement->vatsim_id}/send_notification", $data);

        if (!$response->successful()) {
            throw new \Exception("Failed to send notification: " . $response->body());
        }
    }
}