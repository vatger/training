<?php

namespace App\Console\Commands;

use App\Models\WaitingListEntry;
use App\Services\VatsimActivityService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncWaitingListActivity extends Command
{
    protected $signature = 'waitinglist:sync-activity 
                            {--batch-size=50 : Size of batches when processing}';

    protected $description = 'Sync waiting list activity from VATSIM for all entries';

    protected VatsimActivityService $activityService;

    public function __construct(VatsimActivityService $activityService)
    {
        parent::__construct();
        $this->activityService = $activityService;
    }

    public function handle(): int
    {
        $this->info('Starting waiting list activity sync for ALL entries...');

        try {
            $this->updateAllActivities();

            $this->info('Waiting list activity sync completed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error during sync: ' . $e->getMessage());
            Log::error('Waiting list activity sync error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    protected function updateAllActivities(): void
    {
        $batchSize = (int) $this->option('batch-size');

        $totalCount = WaitingListEntry::whereHas('course', function ($query) {
            $query->where('type', 'RTG');
        })->count();

        if ($totalCount === 0) {
            $this->info('No RTG waiting list entries to update');
            return;
        }

        $this->info("Updating activity for {$totalCount} RTG waiting list entry/entries...");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();
        
        $processedCount = 0;

        WaitingListEntry::whereHas('course', function ($query) {
            $query->where('type', 'RTG');
        })
        ->orderBy('hours_updated', 'asc')
            ->chunk($batchSize, function ($entries) use (&$processedCount, &$bar) {
            foreach ($entries as $entry) {
                $this->updateEntryActivity($entry);
                $processedCount++;
                    $bar->advance();
            }

                sleep(1);
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Completed updating {$processedCount} entries.");
    }

    protected function updateEntryActivity(WaitingListEntry $entry): void
    {
        try {
            $course = $entry->course;
            $user = $entry->user;

            if (!$user->isVatsimUser()) {
                return;
            }

            $activityHours = $this->getActivityHours($course, $user);

            $entry->activity = $activityHours;
            $entry->hours_updated = now();
            $entry->save();

        } catch (\Exception $e) {
            Log::error('Failed to update waiting list activity', [
                'entry_id' => $entry->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function getActivityHours($course, $user): float
    {
        $airport = $course->airport_icao;
        $position = $course->position;
        $fir = substr($course->mentorGroup->name, 0, 4);

        $start = Carbon::now()->subDays(60)->format('Y-m-d');
        $apiUrl = "https://stats.vatsim-germany.org/api/atc/{$user->vatsim_id}/sessions/?cid={$user->vatsim_id}&start_date={$start}";

        try {
            $response = \Http::timeout(15)->retry(2, 1000)->get($apiUrl);
            
            if (!$response->successful()) {
                Log::warning('VATSIM Germany API request failed for waiting list', [
                    'vatsim_id' => $user->vatsim_id,
                    'status' => $response->status()
                ]);
                return -1;
            }

            $connections = $response->json();
            if (!is_array($connections)) {
                $connections = [];
            }

            return match($position) {
                'GND', 'TWR' => $this->calculateS1TowerHours($connections, $fir),
                'APP' => $this->calculateAppHours($connections, $airport),
                'CTR' => 10,
                default => -1
            };

        } catch (\Exception $e) {
            Log::error('Error fetching VATSIM connections from vatsim-germany.org for waiting list', [
                'vatsim_id' => $user->vatsim_id,
                'error' => $e->getMessage()
            ]);
            return -1;
        }
    }

    protected function calculateS1TowerHours(array $connections, string $fir): float
    {
        $url = "https://raw.githubusercontent.com/VATGER-Nav/datahub/refs/heads/production/api/{$fir}/twr.json";
        
        try {
            $response = \Http::get($url);
            if (!$response->successful()) {
                Log::warning("Failed to fetch datahub for {$fir}");
                return 0;
            }

            $hub = $response->json();
            
            $stations = collect($hub)
                ->filter(function ($station) {
                    return isset($station['s1_twr']) 
                        && $station['s1_twr'] === true
                        && !str_contains($station['logon'] ?? '', '_I_');
                })
                ->pluck('logon')
                ->toArray();

            $totalMinutes = 0;
            foreach ($connections as $session) {
                $callsign = $session['callsign'] ?? '';
                
                foreach ($stations as $station) {
                    if ($this->equalStr($callsign, $station)) {
                        $totalMinutes += floatval($session['minutes_online'] ?? 0);
                        break;
                    }
                }
            }

            return $totalMinutes / 60;

        } catch (\Exception $e) {
            Log::error('Error calculating S1 tower hours', [
                'fir' => $fir,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    protected function calculateAppHours(array $connections, string $airport): float
    {
        $totalMinutes = 0;
        
        foreach ($connections as $session) {
            $callsign = $session['callsign'] ?? '';
            $parts = explode('_', $callsign);

            if (
                count($parts) >= 2 &&
                $parts[0] === $airport &&
                end($parts) === 'TWR'
            ) {
                $totalMinutes += floatval($session['minutes_online'] ?? 0);
            }
        }

        return $totalMinutes / 60;
    }

    protected function equalStr(string $a, string $b): bool
    {
        $partsA = explode('_', $a);
        $partsB = explode('_', $b);
        
        if (empty($partsA) || empty($partsB)) {
            return false;
        }

        return $partsA[0] === $partsB[0] && end($partsA) === end($partsB);
    }
}