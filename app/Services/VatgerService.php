<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VatgerService
{
    protected $apiKey;
    protected $baseUrl = 'http://vatsim-germany.org/api';

    public function __construct()
    {
        $this->apiKey = config('services.vatger.api_key');
    }

    public function postConfirmedCpts(array $cpts): array
    {
        if (empty($cpts)) {
            return ['success' => true, 'message' => 'No CPTs to post'];
        }

        try {
            $tableData = [];
            foreach ($cpts as $cpt) {
                $tableData[] = [
                    'trainee' => $cpt['trainee'],
                    'date' => $cpt['date'],
                    'position' => $cpt['position'],
                ];
            }

            $data = [
                'text_data' => 'The above CPTs have been confirmed.',
                'table_data' => $tableData,
            ];

            Log::info('Posting confirmed CPTs to VATGER API', [
                'cpt_count' => count($cpts),
            ]);

            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(10)
            ->post("{$this->baseUrl}/board/post/cpt", $data);

            if ($response->successful()) {
                Log::info('Successfully posted CPTs to VATGER API');
                return ['success' => true];
            }

            $errorMessage = $response->json()['message'] ?? 'Failed to post CPTs';

            Log::error('Failed to post CPTs to VATGER API', [
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('Exception posting CPTs to VATGER API', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function sendNotification(int $vatsimId, string $title, string $message, string $linkText, string $linkUrl): array
    {
        try {
            $data = [
                'title' => $title,
                'message' => $message,
                'source_name' => 'VATGER ATD',
                'link_text' => $linkText,
                'link_url' => $linkUrl,
                'via' => 'board.ping',
            ];

            Log::info('Sending notification to user', [
                'vatsim_id' => $vatsimId,
                'title' => $title,
            ]);

            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
            ])
            ->timeout(10)
            ->post("{$this->baseUrl}/user/{$vatsimId}/send_notification", $data);

            if ($response->successful()) {
                Log::info('Successfully sent notification', ['vatsim_id' => $vatsimId]);
                return ['success' => true];
            }

            Log::error('Failed to send notification', [
                'vatsim_id' => $vatsimId,
                'status' => $response->status(),
            ]);

            return ['success' => false];

        } catch (\Exception $e) {
            Log::error('Exception sending notification', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false];
        }
    }
}