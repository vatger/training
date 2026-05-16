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

    protected function checkUser(int $vatsimId): void
    {
        try {
            $entry = RosterEntry::firstOrCreate(
                ['user_id' => $vatsimId],
                ['last_session' => null, 'removal_date' => null]
            );

            try {
                $lastSession = $this->getLastSession($vatsimId);

                if ($lastSession instanceof Carbon && $lastSession->year > 2000) {
                    $entry->last_session = $lastSession;
                    $entry->save();
                }
            } catch (\Exception $e) {
                Log::warning('Session fetch failed', [
                    'vatsim_id' => $vatsimId,
                    'error' => $e->getMessage(),
                ]);
            }

            if (!$entry->last_session) {
                return;
            }

            $inactiveDays = $entry->last_session->diffInDays(now());

            $WARNING_THRESHOLD = 330; // ~11 months
            $REMOVAL_THRESHOLD = 365; // 12 months
            $GRACE_DAYS = 35;


            if ($inactiveDays >= $WARNING_THRESHOLD && !$entry->removal_date) {

                $this->sendRemovalWarning($vatsimId);

                $entry->removal_date = now()->addDays($GRACE_DAYS);
                $entry->save();

                return;
            }

            if (
                $inactiveDays >= $REMOVAL_THRESHOLD &&
                $entry->removal_date &&
                now()->gte($entry->removal_date)
            ) {

                $this->removeFromRoster($vatsimId);
                $entry->delete();

                return;
            }

        } catch (\Throwable $e) {
            Log::error('USER CHECK FAILED', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getRoster(): array
    {
        $response = Http::withHeaders([
            'X-API-KEY' => config('services.vateud.token'),
            'Accept' => 'application/json',
        ])->get('https://core.vateud.net/api/facility/roster');

        return $response->successful()
            ? ($response->json()['data']['controllers'] ?? [])
            : [];
    }

    protected function getLastSession(int $vatsimId): Carbon
    {
        $date = now()->subYear();

        $response = Http::timeout(15)->get(
            "http://stats.vatsim-germany.org/api/atc/{$vatsimId}/sessions/?start_date={$date->format('Y-m-d')}"
        );

        if (!$response->successful()) {
            throw new \Exception('Session API failed');
        }

        $latest = null;

        foreach ($response->json() as $session) {
            $prefix = substr($session['callsign'] ?? '', 0, 2);

            if (!in_array($prefix, ['ED', 'ET'])) {
                continue;
            }

            if (!empty($session['disconnected_at'])) {
                $time = Carbon::parse($session['disconnected_at']);

                if (!$latest || $time->gt($latest)) {
                    $latest = $time;
                }
            }
        }

        return $latest ?? Carbon::createFromTimestamp(0);
    }

    protected function checkS1Status(int $vatsimId): array
    {
        try {
            $apiKey = config('services.vatger.api_key');
            $apiBaseUrl = config('services.vatger.api_url');

            if (!$apiKey) {
                Log::warning('VATGER API key missing');
                return [false, null];
            }

            $response = Http::withHeaders([
                'Authorization' => "Token {$apiKey}",
                'Accept' => 'application/json',
            ])->timeout(10)
                ->get("{$apiBaseUrl}/user/{$vatsimId}/");

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
        $message =
            "You have not controlled in the past 11 months. " .
            "To remain on the VATSIM Germany roster, please log in and control within the next 35 days. " .
            "Otherwise, your account will be removed from the roster. " .
            "If you believe this is a mistake, please contact the ATD.";

        $result = $this->vatgerService->sendNotification(
            $vatsimId,
            'Removal from VATSIM Germany Roster',
            $message,
            'VATGER ATD',
            'https://vatsim-germany.org'
        );

        if (!$result['success']) {
            Log::error('Failed to send roster removal warning', [
                'vatsim_id' => $vatsimId,
            ]);
        } else {
            ActivityLogger::log(
                'roster.notified',
                $vatsimId,
                "Notified roster removal for $vatsimId",
            );
        }
    }

    protected function removeFromRoster(int $vatsimId): void
    {
        $success = $this->vatEudService->removeRosterAndEndorsements($vatsimId);

        if (!$success) {
            Log::error('Roster removal failed at VATEUD', [
                'vatsim_id' => $vatsimId,
            ]);
            return;
        }

        WaitingListEntry::whereHas('user', function ($q) use ($vatsimId) {
            $q->where('vatsim_id', $vatsimId);
        })->delete();

        ActivityLogger::log(
            'roster.removed',
            null,
            "User {$vatsimId} removed from roster due to inactivity",
            [
                'vatsim_id' => $vatsimId,
                'reason' => 'inactivity',
                'removed_by' => 'system',
            ]
        );

        Log::warning("REMOVAL COMPLETE: {$vatsimId}");
    }
}
