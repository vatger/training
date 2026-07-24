<?php

namespace App\Integrations\Vatger;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VatgerClient implements VatgerClientInterface
{
    private ?string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.vatger.api_key');
        $this->baseUrl = config('services.vatger.api_url');
    }

    public function postConfirmedCpts(array $cpts): array
    {
        if (empty($cpts)) {
            return ['success' => true];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
                'Accept' => 'application/json',
            ])
                ->timeout(10)
                ->post("{$this->baseUrl}/board/post/cpt", [
                    'text_data' => 'The above CPTs have been confirmed.',
                    'table_data' => $cpts,
                ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            $message = $response->json()['message'] ?? 'Failed to post CPTs';
            Log::error('Failed to post CPTs to VATGER API', ['status' => $response->status(), 'error' => $message]);

            return ['success' => false, 'message' => $message];
        } catch (\Exception $e) {
            Log::error('Exception posting CPTs to VATGER API', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function sendNotification(
        int $vatsimId,
        string $title,
        string $message,
        string $sourceName,
        string $linkUrl,
        string $linkText = '',
    ): array {
        try {
            $response = Http::withHeaders(['Authorization' => "Token {$this->apiKey}"])
                ->timeout(10)
                ->post("{$this->baseUrl}/user/{$vatsimId}/send_notification", [
                    'title' => $title,
                    'message' => $message,
                    'source_name' => $sourceName,
                    'link_text' => $linkText,
                    'link_url' => $linkUrl,
                    'via' => 'board.ping',
                ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            Log::error('Failed to send Vatger notification', ['vatsim_id' => $vatsimId, 'status' => $response->status()]);

            return ['success' => false];
        } catch (\Exception $e) {
            Log::error('Exception sending Vatger notification', ['vatsim_id' => $vatsimId, 'error' => $e->getMessage()]);

            return ['success' => false];
        }
    }

    public function getLastGermanSession(int $vatsimId): ?Carbon
    {
        try {
            $start = Carbon::now()->subYears(2)->format('Y-m-d');

            $response = Http::timeout(15)
                ->retry(2, 1000)
                ->get("http://stats.vatsim-germany.org/api/atc/{$vatsimId}/sessions/", [
                    'start_date' => $start,
                ]);

            if (! $response->successful()) {
                Log::warning('Failed to fetch last German session', [
                    'vatsim_id' => $vatsimId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $sessions = $response->json();

            if (! is_array($sessions)) {
                return null;
            }

            $lastSession = null;

            foreach ($sessions as $session) {
                if (! is_array($session)) {
                    continue;
                }

                $date = $this->parseSessionDate($session);

                if ($date && ($lastSession === null || $date->greaterThan($lastSession))) {
                    $lastSession = $date;
                }
            }

            return $lastSession;
        } catch (\Exception $e) {
            Log::error('Exception fetching last German session', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function parseSessionDate(array $session): ?Carbon
    {
        foreach (['disconnected_at', 'connected_at', 'start', 'end', 'created_at', 'date'] as $dateField) {
            if (isset($session[$dateField])) {
                try {
                    return Carbon::parse($session[$dateField]);
                } catch (\Exception $e) {
                    // Continue to next field
                }
            }
        }

        return null;
    }
}
