<?php

namespace App\Console\Commands;

use App\Models\RosterEntry;
use App\Models\WaitingListEntry;
use App\Models\User;
use App\Services\VatEudService;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckRosterStatus extends Command
{
    protected $signature = 'roster:check';
    protected $description = 'Check roster status and remove inactive users';

    protected VatEudService $vatEudService;

    public function __construct(VatEudService $vatEudService)
    {
        parent::__construct();
        $this->vatEudService = $vatEudService;
    }

    public function handle(): int
    {
        $this->info('Starting roster check...');

        try {
            $roster = $this->getRoster();
            
            if (empty($roster)) {
                $this->error('Failed to fetch roster from VatEUD');
                return 1;
            }

            $this->info('Found ' . count($roster) . ' users on roster');

            foreach ($roster as $vatsimId) {
                $this->checkUser($vatsimId);
            }

            $this->info('Roster check completed successfully.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Error during roster check: ' . $e->getMessage());
            Log::error('Roster check error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    protected function getRoster(): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => config('services.vateud.token'),
                'Accept' => 'application/json',
                'User-Agent' => 'VATGER Training System',
            ])->get('https://core.vateud.net/api/facility/roster');

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['controllers'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch roster from VatEUD', ['error' => $e->getMessage()]);
        }

        return [];
    }

    protected function checkUser(int $vatsimId): void
    {
        try {
            $entry = RosterEntry::firstOrCreate(
                ['user_id' => $vatsimId],
                [
                    'last_session' => Carbon::createFromTimestamp(0),
                    'removal_date' => null
                ]
            );

            if ($entry->last_session && !$entry->last_session->timezone) {
                $entry->last_session = Carbon::parse($entry->last_session)->timezone('UTC');
                $entry->save();
            }

            if ($entry->last_session && now()->diffInDays($entry->last_session) < (11 * 30)) {
                return;
            }

            try {
                $lastSession = $this->getLastSession($vatsimId);
                $entry->last_session = $lastSession;
                $entry->save();
            } catch (\Exception $e) {
                $this->warn("Error getting last session for {$vatsimId}: " . $e->getMessage());
                return;
            }

            if ($entry->last_session->lt(now()->subDays(366))) {
                if ($entry->removal_date && $entry->removal_date->lt(now())) {
                    $this->removeFromRoster($vatsimId);
                    $entry->delete();
                    return;
                }
            }

            if ($entry->last_session->lt(now()->subDays(11 * 30))) {
                try {
                    [$isRecentS1, $ratingChangeDate] = $this->checkS1Status($vatsimId);

                    if ($isRecentS1) {
                        $entry->last_session = $ratingChangeDate;
                        $entry->removal_date = null;
                        $entry->save();
                        return;
                    }
                } catch (\Exception $e) {
                    $this->warn("Error checking rating for {$vatsimId}: " . $e->getMessage());
                }

                if (!$entry->removal_date) {
                    $this->sendRemovalWarning($vatsimId);
                    $entry->removal_date = now()->addDays(35);
                    $entry->save();
                    $this->info("Set removal date for {$vatsimId}: " . $entry->removal_date->format('Y-m-d'));
                }
            } else {
                if ($entry->removal_date) {
                    $entry->removal_date = null;
                    $entry->save();
                }
            }

        } catch (\Exception $e) {
            $this->error("Error checking user {$vatsimId}: " . $e->getMessage());
            Log::error('Error in roster check for user', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function getLastSession(int $vatsimId): Carbon
    {
        $date = now()->subDays(365);
        $apiUrl = "https://stats.vatsim-germany.org/api/atc/{$vatsimId}/sessions/?cid={$vatsimId}&start_date={$date->format('Y-m-d')}";

        try {
            $response = Http::timeout(15)->get($apiUrl);
            
            if (!$response->successful()) {
                throw new \Exception("API request failed with status: " . $response->status());
            }

            $connections = $response->json();
            if (!is_array($connections)) {
                $connections = [];
            }
            
            foreach ($connections as $connection) {
                $callsign = $connection['callsign'] ?? '';
                $prefix = substr($callsign, 0, 2);
                
                if (in_array($prefix, ['ED', 'ET'])) {
                    $endTime = $connection['disconnected_at'] ?? null;
                    if ($endTime) {
                        return Carbon::parse($endTime)->timezone('UTC');
                    }
                }
            }

            $this->warn("No German connections found for user {$vatsimId}");
            return Carbon::createFromTimestamp(0)->timezone('UTC');

        } catch (\Exception $e) {
            Log::error('Error fetching last session from vatsim-germany.org', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function checkS1Status(int $vatsimId): array
    {
        try {
            $apiKey = config('services.vatger.api_key');

            if (!$apiKey) {
                Log::warning('VATGER API key missing');
                return [false, null];
            }

            $response = Http::withHeaders([
                'Authorization' => "Token {$apiKey}",
                'Accept' => 'application/json',
            ])->timeout(10)
                ->get("https://vatsim-germany.org/api/user/{$vatsimId}/");

            if (!$response->successful()) {
                Log::warning('Failed S1 rating fetch', [
                    'status' => $response->status()
                ]);
                return [false, null];
            }

            $data = $response->json();

            $rating = $data['atc_rating'] ?? null;

            if ($rating == 2) {
                $ratingChange = $data['lastratingchange'] ?? null;

                if ($ratingChange) {
                    $date = Carbon::parse($ratingChange)->timezone('UTC');
                    $recent = now()->diffInDays($date) < (11 * 30);
                    return [$recent, $date];
                }
            }

            return [false, null];

        } catch (\Exception $e) {
            Log::error('Error checking S1 status', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function sendRemovalWarning(int $vatsimId): void
    {   
        $apiKey = config('services.vatger.api_key');
        
        if (!$apiKey) {
            Log::warning('VATGER API key not configured, skipping removal notification');
            return;
        }

        $message = "You have not controlled in the past 11 months. " .
                   "If you want to stay on the VATSIM Germany roster, " .
                   "please log in to the VATSIM network and control at least once in the next 35 days. " .
                   "If you do not, your account will be removed from the roster. " .
                   "If you believe this is a mistake, please contact the ATD.";

        $data = [
            'title' => 'Removal from VATSIM Germany Roster',
            'message' => $message,
            'source_name' => 'VATGER ATD',
            'via' => 'board.ping',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$apiKey}",
            ])->post("https://vatsim-germany.org/api/user/{$vatsimId}/send_notification", $data);

            if (!$response->successful()) {
                Log::warning('Failed to send removal notification', [
                    'vatsim_id' => $vatsimId,
                    'status' => $response->status()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending removal notification', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function removeFromRoster(int $vatsimId): void
    {
        try {
            $this->info("Removing user {$vatsimId} from roster...");

            $user = User::where('vatsim_id', $vatsimId)->first();

            $this->vatEudService->removeRosterAndEndorsements($vatsimId);

            WaitingListEntry::whereHas('user', function ($query) use ($vatsimId) {
                $query->where('vatsim_id', $vatsimId);
            })->delete();

            if ($user) {
                ActivityLogger::log(
                    'roster.removed',
                    $user,
                    "User {$user->name} (VATSIM ID: {$vatsimId}) removed from roster due to inactivity",
                    [
                        'vatsim_id' => $vatsimId,
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'reason' => 'inactivity',
                        'removed_by' => 'system',
                    ]
                );
            } else {
                ActivityLogger::log(
                    'roster.removed',
                    null,
                    "VATSIM ID {$vatsimId} removed from roster due to inactivity",
                    [
                        'vatsim_id' => $vatsimId,
                        'reason' => 'inactivity',
                        'removed_by' => 'system',
                    ]
                );
            }

            Log::info('User removed from roster', [
                'vatsim_id' => $vatsimId
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing user from roster', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage()
            ]);
        }
    }
}