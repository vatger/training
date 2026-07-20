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
}
