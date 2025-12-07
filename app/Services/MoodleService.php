<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MoodleService
{
    protected $apiKey;
    protected $baseUrl = 'https://vatsim-germany.org/api/moodle';
    protected $cacheTtl;

    public function __construct()
    {
        $this->apiKey = config('services.vatger.api_key');
        $this->cacheTtl = config('services.moodle.cache_ttl', 600);
    }

    public function userExists(int $vatsimId): bool
    {
        $cacheKey = "moodle:user_exists:{$vatsimId}";

        return Cache::remember($cacheKey, now()->addSeconds($this->cacheTtl), function () use ($vatsimId) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Token {$this->apiKey}",
                ])
                    ->timeout(3)
                    ->retry(2, 500)
                ->get("{$this->baseUrl}/user/{$vatsimId}");

                if ($response->successful()) {
                    $data = $response->json();
                    return isset($data['id']);
                }

                Log::warning('Moodle user check failed', [
                    'vatsim_id' => $vatsimId,
                    'status' => $response->status()
                ]);

                return false;
            } catch (\Exception $e) {
                Log::error('Error checking Moodle user existence', [
                    'vatsim_id' => $vatsimId,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
    }

    public function getCourseCompletion(int $vatsimId, int $courseId): bool
    {
        $cacheKey = "moodle:completion:{$vatsimId}:{$courseId}";

        return Cache::remember($cacheKey, now()->addSeconds($this->cacheTtl), function () use ($vatsimId, $courseId) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Token {$this->apiKey}",
                ])
                    ->timeout(5)
                    ->retry(2, 500)
                ->get("{$this->baseUrl}/course/{$courseId}/user/{$vatsimId}/completion");

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['completed'] ?? false;
                }

                Log::warning('Moodle completion check failed', [
                    'vatsim_id' => $vatsimId,
                    'course_id' => $courseId,
                    'status' => $response->status()
                ]);

                return false;
            } catch (\Exception $e) {
                Log::error('Error checking Moodle course completion', [
                    'vatsim_id' => $vatsimId,
                    'course_id' => $courseId,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
    }

    public function getCourseName(int $courseId): ?string
    {
        $cacheKey = "moodle:course_name:{$courseId}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($courseId) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Token {$this->apiKey}",
                ])
                    ->timeout(5)
                ->get("{$this->baseUrl}/course/{$courseId}");

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['displayname'] ?? null;
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Error fetching Moodle course name', [
                    'course_id' => $courseId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    public function checkAllCoursesCompleted(int $vatsimId, array $courseIds): bool
    {
        if (empty($courseIds)) {
            return true;
        }

        foreach ($courseIds as $courseId) {
            if (!$this->getCourseCompletion($vatsimId, $courseId)) {
                return false;
            }
        }

        return true;
    }

    public function getCoursesCompletionStatus(int $vatsimId, array $courseIds): array
    {
        $results = [];

        foreach ($courseIds as $courseId) {
            $results[$courseId] = [
                'id' => $courseId,
                'name' => $this->getCourseName($courseId),
                'completed' => $this->getCourseCompletion($vatsimId, $courseId),
            ];
        }

        return $results;
    }

    public function enrollUser(int $vatsimId, int $courseId): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Token {$this->apiKey}",
            ])
                ->timeout(10)
                ->get("{$this->baseUrl}/course/{$courseId}/user/{$vatsimId}/enrol");

            if ($response->successful()) {
                Cache::forget("moodle:completion:{$vatsimId}:{$courseId}");

                Log::info('User enrolled in Moodle course', [
                    'vatsim_id' => $vatsimId,
                    'course_id' => $courseId
                ]);

                return true;
            }

            Log::warning('Moodle enrollment failed', [
                'vatsim_id' => $vatsimId,
                'course_id' => $courseId,
                'status' => $response->status()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error enrolling user in Moodle course', [
                'vatsim_id' => $vatsimId,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function enrollUserInCourses(int $vatsimId, array $courseIds): void
    {
        foreach ($courseIds as $courseId) {
            $this->enrollUser($vatsimId, $courseId);
        }
    }

    public function clearUserCache(int $vatsimId): void
    {
        Cache::forget("moodle:user_exists:{$vatsimId}");
    }
}