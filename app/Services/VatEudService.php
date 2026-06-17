<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VatEudService
{
    protected array $headers;

    protected string $baseUrl = 'https://core.vateud.net/api';

    protected const MIN_SOLO_DURATION_DAYS = 7;

    public function __construct()
    {
        $this->headers = [
            'X-API-KEY' => config('services.vateud.token'),
            'Accept' => 'application/json',
            'User-Agent' => 'VATGER Training System',
        ];
    }

    public function getTier1Endorsements(): array
    {
        if (config('services.vateud.use_mock', false)) {
            Log::info('Using mock Tier 1 data');

            return $this->getMockTier1Data();
        }

        try {
            $cacheKey = 'vateud:tier1_endorsements';

            return Cache::remember($cacheKey, now()->addMinutes(10), function () {
                $response = Http::withHeaders($this->headers)
                    ->timeout(10)
                    ->get("{$this->baseUrl}/facility/endorsements/tier-1");

                if (! $response->successful()) {
                    Log::error('Failed to fetch Tier 1 endorsements', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return [];
                }

                $data = $response->json();

                if (isset($data['data']) && is_array($data['data'])) {
                    $endorsements = $data['data'];
                } elseif (is_array($data)) {
                    $endorsements = $data;
                } else {
                    Log::warning('Unexpected Tier 1 endorsements response structure', [
                        'data' => $data,
                    ]);

                    return [];
                }

                return $this->sortEndorsements($endorsements);
            });
        } catch (\Throwable $e) {
            Log::error('Error fetching Tier 1 endorsements', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    public function createTier1Endorsement(
        int $userCid,
        string $position,
        int $instructorCid
    ): array {
        if (config('services.vateud.use_mock', false)) {
            Log::info('Mock: Creating Tier 1 endorsement', [
                'user_cid' => $userCid,
                'position' => $position,
                'instructor_cid' => config('services.vateud.atd_lead_cid', 1441619),
            ]);

            return ['success' => true];
        }

        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->post("{$this->baseUrl}/facility/endorsements/tier-1", [
                    'user_cid' => $userCid,
                    'position' => $position,
                    'instructor_cid' => config('services.vateud.atd_lead_cid', 1441619),
                ]);

            if ($response->successful()) {
                Cache::forget('vateud:tier1_endorsements');

                return ['success' => true];
            }

            $data = $response->json();

            $errorMessage = is_array($data)
                ? ($data['message'] ?? 'Failed to create endorsement')
                : 'Failed to create endorsement';

            Log::error('Failed to create Tier 1 endorsement', [
                'user_cid' => $userCid,
                'position' => $position,
                'status' => $response->status(),
                'error' => $errorMessage,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error('Exception creating Tier 1 endorsement', [
                'user_cid' => $userCid,
                'position' => $position,
                'instructor_cid' => $instructorCid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getTier2Endorsements(): array
    {
        if (config('services.vateud.use_mock', false)) {
            return $this->getMockTier2Data();
        }

        try {
            $cacheKey = 'vateud:tier2_endorsements';

            return Cache::remember($cacheKey, now()->addMinutes(10), function () {
                $response = Http::withHeaders($this->headers)
                    ->timeout(10)
                    ->get("{$this->baseUrl}/facility/endorsements/tier-2");

                if (! $response->successful()) {
                    Log::error('Failed to fetch Tier 2 endorsements', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return [];
                }

                $data = $response->json();

                if (isset($data['data']) && is_array($data['data'])) {
                    return $data['data'];
                }

                if (is_array($data)) {
                    return $data;
                }

                Log::warning('Unexpected Tier 2 endorsements response structure', [
                    'data' => $data,
                ]);

                return [];
            });
        } catch (\Throwable $e) {
            Log::error('Error fetching Tier 2 endorsements', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    public function getSoloEndorsements(): array
    {
        if (config('services.vateud.use_mock', false)) {
            return $this->getMockSoloData();
        }

        try {
            $cacheKey = 'vateud:solo_endorsements';

            return Cache::remember($cacheKey, now()->addMinutes(5), function () {
                $response = Http::withHeaders($this->headers)
                    ->timeout(10)
                    ->get("{$this->baseUrl}/facility/endorsements/solo");

                if (! $response->successful()) {
                    Log::error('Failed to fetch solo endorsements', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return [];
                }

                $data = $response->json();

                if (isset($data['data']) && is_array($data['data'])) {
                    return $data['data'];
                }

                if (is_array($data)) {
                    return $data;
                }

                Log::warning('Unexpected solo endorsements response structure', [
                    'data' => $data,
                ]);

                return [];
            });
        } catch (\Throwable $e) {
            Log::error('Error fetching solo endorsements', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    public function removeRosterAndEndorsements(int $vatsimId): bool
    {
        if (config('services.vateud.use_mock', false)) {
            return true;
        }

        try {
            $success = true;

            $rosterResponse = Http::withHeaders($this->headers)
                ->timeout(10)
                ->delete("{$this->baseUrl}/facility/roster/{$vatsimId}");

            if (! $rosterResponse->successful()) {
                Log::error('Roster removal failed', [
                    'vatsim_id' => $vatsimId,
                    'status' => $rosterResponse->status(),
                    'body' => $rosterResponse->body(),
                ]);

                return false;
            }

            $tier1 = collect($this->getTier1Endorsements())
                ->where('user_cid', $vatsimId);

            foreach ($tier1 as $endorsement) {
                if (! isset($endorsement['id'])) {
                    Log::warning('Tier 1 endorsement missing ID', [
                        'endorsement' => $endorsement,
                    ]);

                    $success = false;

                    continue;
                }

                try {
                    $response = Http::withHeaders($this->headers)
                        ->timeout(10)
                        ->delete(
                            "{$this->baseUrl}/facility/endorsements/tier-1/{$endorsement['id']}"
                        );

                    if (! $response->successful()) {
                        Log::warning('Failed to delete Tier 1 endorsement', [
                            'vatsim_id' => $vatsimId,
                            'endorsement_id' => $endorsement['id'],
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);

                        $success = false;
                    }
                } catch (\Throwable $e) {
                    Log::error('Exception while deleting Tier 1 endorsement', [
                        'vatsim_id' => $vatsimId,
                        'endorsement_id' => $endorsement['id'],
                        'error' => $e->getMessage(),
                    ]);

                    $success = false;
                }
            }

            $tier2 = collect($this->getTier2Endorsements())
                ->where('user_cid', $vatsimId);

            foreach ($tier2 as $endorsement) {
                if (! isset($endorsement['id'])) {
                    Log::warning('Tier 2 endorsement missing ID', [
                        'endorsement' => $endorsement,
                    ]);

                    $success = false;

                    continue;
                }

                try {
                    $response = Http::withHeaders($this->headers)
                        ->timeout(10)
                        ->delete(
                            "{$this->baseUrl}/facility/endorsements/tier-2/{$endorsement['id']}"
                        );

                    if (! $response->successful()) {
                        Log::warning('Failed to delete Tier 2 endorsement', [
                            'vatsim_id' => $vatsimId,
                            'endorsement_id' => $endorsement['id'],
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);

                        $success = false;
                    }
                } catch (\Throwable $e) {
                    Log::error('Exception while deleting Tier 2 endorsement', [
                        'vatsim_id' => $vatsimId,
                        'endorsement_id' => $endorsement['id'],
                        'error' => $e->getMessage(),
                    ]);

                    $success = false;
                }
            }

            Cache::forget('vateud:tier1_endorsements');
            Cache::forget('vateud:tier2_endorsements');

            return $success;
        } catch (\Throwable $e) {
            Log::error('Error removing roster and endorsements', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function removeTier1Endorsement(int $endorsementId): bool
    {
        if (config('services.vateud.use_mock', false)) {
            return true;
        }

        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->delete("{$this->baseUrl}/facility/endorsements/tier-1/{$endorsementId}");

            if ($response->successful()) {
                Cache::forget('vateud:tier1_endorsements');
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Error removing Tier 1 endorsement', [
                'endorsement_id' => $endorsementId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function createTier2Endorsement(
        int $userCid,
        string $position,
        int $instructorCid
    ): bool {
        if (config('services.vateud.use_mock', false)) {
            return true;
        }

        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->post("{$this->baseUrl}/facility/endorsements/tier-2", [
                    'user_cid' => $userCid,
                    'position' => $position,
                    'instructor_cid' => config('services.vateud.atd_lead_cid', 1441619),
                ]);

            if ($response->successful()) {
                Cache::forget('vateud:tier2_endorsements');
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Error creating Tier 2 endorsement', [
                'user_cid' => $userCid,
                'position' => $position,
                'instructor_cid' => $instructorCid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function createSoloEndorsement(
        int $userCid,
        string $position,
        string $expireAt,
        int $instructorCid
    ): array {
        if (Carbon::parse($expireAt)->lt(now()->addDays(self::MIN_SOLO_DURATION_DAYS))) {
            return [
                'success' => false,
                'message' => 'Solo endorsement duration must be at least '.self::MIN_SOLO_DURATION_DAYS.' days.',
            ];
        }
 
        if (config('services.vateud.use_mock', false)) {
            return ['success' => true];
        }
 
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->post("{$this->baseUrl}/facility/endorsements/solo", [
                    'user_cid' => $userCid,
                    'position' => $position,
                    'expire_at' => $expireAt,
                    'instructor_cid' => config('services.vateud.atd_lead_cid', 1441619),
                ]);
 
            if ($response->successful()) {
                Cache::forget('vateud:solo_endorsements');
 
                return ['success' => true];
            }
 
            $data = $response->json();
 
            $errorMessage = is_array($data)
                ? ($data['message'] ?? 'Failed to create solo endorsement')
                : 'Failed to create solo endorsement';
 
            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error('Exception creating solo endorsement', [
                'user_cid' => $userCid,
                'position' => $position,
                'instructor_cid' => $instructorCid,
                'error' => $e->getMessage(),
            ]);
 
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function removeSoloEndorsement(int $soloId): bool
    {
        if (config('services.vateud.use_mock', false)) {
            return true;
        }

        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->delete("{$this->baseUrl}/facility/endorsements/solo/{$soloId}");

            if ($response->successful()) {
                Cache::forget('vateud:solo_endorsements');
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Error removing solo endorsement', [
                'solo_id' => $soloId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function refreshEndorsementCache(): void
    {
        Cache::forget('vateud:tier1_endorsements');
        Cache::forget('vateud:tier2_endorsements');
        Cache::forget('vateud:solo_endorsements');

        $this->getTier1Endorsements();
        $this->getTier2Endorsements();
        $this->getSoloEndorsements();
    }

    protected function sortEndorsements(array $endorsements): array
    {
        usort($endorsements, function ($a, $b) {
            $sortA = $this->getEndorsementSortKey($a['position'] ?? '');
            $sortB = $this->getEndorsementSortKey($b['position'] ?? '');

            return strcmp($sortA, $sortB);
        });

        return $endorsements;
    }

    protected function getEndorsementSortKey(string $position): string
    {
        if ($position === '') {
            return '999_UNKNOWN';
        }

        if (str_ends_with($position, '_CTR')) {
            $ctrCode = substr($position, 0, -4);

            return "0_CTR_{$ctrCode}";
        }

        $parts = explode('_', $position);

        if (count($parts) >= 2) {
            $airport = $parts[0];
            $endorsementType = implode('_', array_slice($parts, 1));

            $typePriority = [
                'APP' => 1,
                'TWR' => 2,
                'GNDDEL' => 3,
            ];

            $priority = $typePriority[$endorsementType] ?? 9;

            return sprintf(
                '1_%s_%02d',
                $airport,
                $priority
            );
        }

        return "9_{$position}";
    }

    protected function getMockTier1Data(): array
    {
        return [
            [
                'id' => 1,
                'user_cid' => 1601613,
                'instructor_cid' => 1441619,
                'position' => 'EDDL_TWR',
                'facility' => 9,
                'created_at' => '2025-04-19T12:02:38.000000Z',
                'updated_at' => '2025-04-19T12:02:38.000000Z',
            ],
        ];
    }

    protected function getMockTier2Data(): array
    {
        return [
            [
                'id' => 25,
                'user_cid' => 1441619,
                'instructor_cid' => 1441619,
                'position' => 'EDXX_AFIS',
                'facility' => 9,
                'created_at' => '2024-02-29T22:39:33.000000Z',
                'updated_at' => '2024-02-29T22:39:33.000000Z',
            ],
        ];
    }

    protected function getMockSoloData(): array
    {
        return [];
    }

    public function getUserExams(int $vatsimId): array
    {
        if (config('services.vateud.use_mock', false)) {
            return $this->getMockExamData($vatsimId);
        }

        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->get("{$this->baseUrl}/facility/user/{$vatsimId}/exams");

            if (! $response->successful()) {
                Log::error('Failed to fetch user exams', [
                    'vatsim_id' => $vatsimId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'results' => [],
                    'assignments' => [],
                ];
            }

            $data = $response->json();

            if (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
            }

            if (is_array($data)) {
                return [
                    'results' => $data['results'] ?? [],
                    'assignments' => $data['assignments'] ?? [],
                ];
            }

            return [
                'results' => [],
                'assignments' => [],
            ];
        } catch (\Throwable $e) {
            Log::error('Error fetching user exams', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);

            return [
                'results' => [],
                'assignments' => [],
            ];
        }
    }

    public function assignCoreTheoryTest(
        int $vatsimId,
        int $examId,
        int $instructorId
    ): array {
        if (config('services.vateud.use_mock', false)) {
            return [
                'success' => true,
                'message' => 'Test assigned (mock)',
            ];
        }

        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->post("{$this->baseUrl}/facility/training/exams/assign", [
                    'user_cid' => $vatsimId,
                    'exam_id' => $examId,
                    'instructor_cid' => config('services.vateud.atd_lead_cid', 1441619),
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Core theory test assigned successfully',
                ];
            }

            $data = $response->json();

            $errorMessage = is_array($data)
                ? ($data['message'] ?? 'Failed to assign test')
                : 'Failed to assign test';

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error('Exception assigning core theory test', [
                'vatsim_id' => $vatsimId,
                'exam_id' => $examId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while assigning the test',
            ];
        }
    }

    protected function getMockExamData(int $vatsimId): array
    {
        return [
            'results' => [],
            'assignments' => [],
        ];
    }

    public function uploadCptLog(
        int $traineeCid,
        int $examinerCid,
        string $position,
        string $note,
        bool $cptPass,
        string $filePath
    ): array {
        if (config('services.vateud.use_mock', false)) {
            return ['success' => true];
        }

        try {
            $fileContents = file_get_contents($filePath);

            if ($fileContents === false) {
                throw new \RuntimeException(
                    "Unable to read file: {$filePath}"
                );
            }

            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->attach(
                    'file',
                    $fileContents,
                    basename($filePath)
                )
                ->post(
                    "{$this->baseUrl}/facility/user/{$traineeCid}/notes/cpt",
                    [
                        'examiner_cid' => $examinerCid,
                        'position' => $position,
                        'note' => $note,
                        'cpt_pass' => (int) $cptPass,
                    ]
                );

            if ($response->successful()) {
                return ['success' => true];
            }

            $data = $response->json();

            $errorMessage = is_array($data)
                ? ($data['message'] ?? 'Failed to upload CPT log')
                : 'Failed to upload CPT log';

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error('Exception uploading CPT log', [
                'trainee_cid' => $traineeCid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function requestUpgrade(
        int $traineeCid,
        int $instructorCid,
        int $newRating
    ): array {
        if (config('services.vateud.use_mock', false)) {
            return ['success' => true];
        }

        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->post("{$this->baseUrl}/facility/user/{$traineeCid}/upgrade", [
                    'instructor_cid' => $instructorCid,
                    'new_rating' => $newRating,
                ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            $data = $response->json();

            $errorMessage = is_array($data)
                ? ($data['message'] ?? 'Failed to request rating upgrade')
                : 'Failed to request rating upgrade';

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error('Exception requesting rating upgrade', [
                'trainee_cid' => $traineeCid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
