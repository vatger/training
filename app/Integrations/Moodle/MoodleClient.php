<?php

namespace App\Integrations\Moodle;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoodleClient implements MoodleClientInterface
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('services.vatger.api_key');
        $this->baseUrl = config('services.vatger.api_url');
    }

    public function userExists(int $vatsimId): bool
    {
        try {
            $response = Http::withHeaders(['Authorization' => "Token {$this->apiKey}"])
                ->timeout(3)
                ->retry(2, 500)
                ->get("{$this->baseUrl}/moodle/user/{$vatsimId}");

            if ($response->successful()) {
                return isset($response->json()['id']);
            }

            Log::warning('Moodle user check failed', ['vatsim_id' => $vatsimId, 'status' => $response->status()]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error checking Moodle user existence', ['vatsim_id' => $vatsimId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getCourseCompletion(int $vatsimId, int $courseId): bool
    {
        try {
            $response = Http::withHeaders(['Authorization' => "Token {$this->apiKey}"])
                ->timeout(5)
                ->retry(2, 500)
                ->get("{$this->baseUrl}/moodle/course/{$courseId}/user/{$vatsimId}/completion");

            if ($response->successful()) {
                return $response->json()['completed'] ?? false;
            }

            Log::warning('Moodle completion check failed', [
                'vatsim_id' => $vatsimId,
                'course_id' => $courseId,
                'status'    => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCourseName(int $courseId): ?string
    {
        try {
            $response = Http::withHeaders(['Authorization' => "Token {$this->apiKey}"])
                ->timeout(5)
                ->get("{$this->baseUrl}/moodle/course/{$courseId}");

            if ($response->successful()) {
                return $response->json()['displayname'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching Moodle course name', ['course_id' => $courseId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function enrollUser(int $vatsimId, int $courseId): bool
    {
        try {
            $response = Http::withHeaders(['Authorization' => "Token {$this->apiKey}"])
                ->timeout(10)
                ->get("{$this->baseUrl}/moodle/course/{$courseId}/user/{$vatsimId}/enrol");

            if ($response->successful()) {
                return true;
            }

            Log::warning('Moodle enrollment failed', [
                'vatsim_id' => $vatsimId,
                'course_id' => $courseId,
                'status'    => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error enrolling user in Moodle course', [
                'vatsim_id' => $vatsimId,
                'course_id' => $courseId,
                'error'     => $e->getMessage(),
            ]);

            return false;
        }
    }
}