<?php

namespace App\Integrations\VatEud;

use App\Integrations\VatEud\DTOs\SoloEndorsementData;
use App\Integrations\VatEud\DTOs\Tier1EndorsementData;
use App\Integrations\VatEud\DTOs\Tier2EndorsementData;
use App\Integrations\VatEud\DTOs\UserExamsData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VatEudClient implements VatEudClientInterface
{
    private string $baseUrl = 'https://core.vateud.net/api';

    private array $headers;

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
        try {
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
            $raw = $data['data'] ?? (is_array($data) ? $data : []);

            $parsed = array_map(
                fn (array $item) => Tier1EndorsementData::fromApiResponse($item),
                $raw,
            );

            return $this->sortByPosition($parsed);
        } catch (\Throwable $e) {
            Log::error('Error fetching Tier 1 endorsements', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function createTier1Endorsement(int $userCid, string $position, int $instructorCid): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->post("{$this->baseUrl}/facility/endorsements/tier-1", [
                    'user_cid' => $userCid,
                    'position' => $position,
                    'instructor_cid' => config('services.vateud.atd_lead_cid', 1441619),
                ]);

            if (! $response->successful()) {
                Log::error('Failed to create Tier 1 endorsement', [
                    'user_cid' => $userCid,
                    'position' => $position,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Exception creating Tier 1 endorsement', [
                'user_cid' => $userCid,
                'position' => $position,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function deleteTier1Endorsement(int $endorsementId): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->delete("{$this->baseUrl}/facility/endorsements/tier-1/{$endorsementId}");

            if (! $response->successful()) {
                Log::warning('Failed to delete Tier 1 endorsement', [
                    'endorsement_id' => $endorsementId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Exception deleting Tier 1 endorsement', [
                'endorsement_id' => $endorsementId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getTier2Endorsements(): array
    {
        try {
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
            $raw = $data['data'] ?? (is_array($data) ? $data : []);

            return array_map(
                fn (array $item) => Tier2EndorsementData::fromApiResponse($item),
                $raw,
            );
        } catch (\Throwable $e) {
            Log::error('Error fetching Tier 2 endorsements', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function createTier2Endorsement(int $userCid, string $position, int $instructorCid): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->post("{$this->baseUrl}/facility/endorsements/tier-2", [
                    'user_cid' => $userCid,
                    'position' => $position,
                    'instructor_cid' => config('services.vateud.atd_lead_cid', 1441619),
                ]);

            if (! $response->successful()) {
                Log::error('Failed to create Tier 2 endorsement', [
                    'user_cid' => $userCid,
                    'position' => $position,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Exception creating Tier 2 endorsement', [
                'user_cid' => $userCid,
                'position' => $position,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function deleteTier2Endorsement(int $endorsementId): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->delete("{$this->baseUrl}/facility/endorsements/tier-2/{$endorsementId}");

            if (! $response->successful()) {
                Log::warning('Failed to delete Tier 2 endorsement', [
                    'endorsement_id' => $endorsementId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Exception deleting Tier 2 endorsement', [
                'endorsement_id' => $endorsementId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getSoloEndorsements(): array
    {
        try {
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
            $raw = $data['data'] ?? (is_array($data) ? $data : []);

            return array_map(
                fn (array $item) => SoloEndorsementData::fromApiResponse($item),
                $raw,
            );
        } catch (\Throwable $e) {
            Log::error('Error fetching solo endorsements', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function createSoloEndorsement(int $userCid, string $position, string $expireAt, int $instructorCid): array
    {
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
                return ['success' => true];
            }

            $data = $response->json();
            $message = is_array($data) ? ($data['message'] ?? 'Failed to create solo endorsement') : 'Failed to create solo endorsement';

            Log::error('Failed to create solo endorsement', [
                'user_cid' => $userCid,
                'position' => $position,
                'status' => $response->status(),
                'message' => $message,
            ]);

            return ['success' => false, 'message' => $message];
        } catch (\Throwable $e) {
            Log::error('Exception creating solo endorsement', [
                'user_cid' => $userCid,
                'position' => $position,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteSoloEndorsement(int $soloId): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->delete("{$this->baseUrl}/facility/endorsements/solo/{$soloId}");

            if (! $response->successful()) {
                Log::error('Failed to delete solo endorsement', [
                    'solo_id' => $soloId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Exception deleting solo endorsement', [
                'solo_id' => $soloId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function removeRosterAndEndorsements(int $vatsimId): bool
    {
        try {
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

            $success = true;

            foreach ($this->getTier1Endorsements() as $endorsement) {
                if ($endorsement->userCid !== $vatsimId) {
                    continue;
                }

                if (! $this->deleteTier1Endorsement($endorsement->id)) {
                    $success = false;
                }
            }

            foreach ($this->getTier2Endorsements() as $endorsement) {
                if ($endorsement->userCid !== $vatsimId) {
                    continue;
                }

                if (! $this->deleteTier2Endorsement($endorsement->id)) {
                    $success = false;
                }
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('Error removing roster and endorsements', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getUserExams(int $vatsimId): UserExamsData
    {
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

                return UserExamsData::fromApiResponse([]);
            }

            $data = $response->json();
            $raw = $data['data'] ?? $data;

            return UserExamsData::fromApiResponse(is_array($raw) ? $raw : []);
        } catch (\Throwable $e) {
            Log::error('Error fetching user exams', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);

            return UserExamsData::fromApiResponse([]);
        }
    }

    public function assignCoreTheoryTest(int $vatsimId, int $examId, int $instructorCid): array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->post("{$this->baseUrl}/facility/training/exams/assign", [
                    'user_cid' => $vatsimId,
                    'exam_id' => $examId,
                    'instructor_cid' => config('services.vateud.atd_lead_cid', 1441619),
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Core theory test assigned successfully'];
            }

            $data = $response->json();
            $message = is_array($data) ? ($data['message'] ?? 'Failed to assign test') : 'Failed to assign test';

            return ['success' => false, 'message' => $message];
        } catch (\Throwable $e) {
            Log::error('Exception assigning core theory test', [
                'vatsim_id' => $vatsimId,
                'exam_id' => $examId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'An error occurred while assigning the test'];
        }
    }

    public function uploadCptLog(
        int $traineeCid,
        int $examinerCid,
        string $position,
        string $note,
        bool $cptPass,
        string $filePath,
    ): array {
        try {
            $fileContents = file_get_contents($filePath);

            if ($fileContents === false) {
                throw new \RuntimeException("Unable to read file: {$filePath}");
            }

            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->attach('file', $fileContents, basename($filePath))
                ->post("{$this->baseUrl}/facility/user/{$traineeCid}/notes/cpt", [
                    'examiner_cid' => $examinerCid,
                    'position' => $position,
                    'note' => $note,
                    'cpt_pass' => (int) $cptPass,
                ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            $data = $response->json();
            $message = is_array($data) ? ($data['message'] ?? 'Failed to upload CPT log') : 'Failed to upload CPT log';

            return ['success' => false, 'message' => $message];
        } catch (\Throwable $e) {
            Log::error('Exception uploading CPT log', [
                'trainee_cid' => $traineeCid,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function requestUpgrade(int $traineeCid, int $instructorCid, int $newRating): array
    {
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
            $message = is_array($data) ? ($data['message'] ?? 'Failed to request rating upgrade') : 'Failed to request rating upgrade';

            return ['success' => false, 'message' => $message];
        } catch (\Throwable $e) {
            Log::error('Exception requesting rating upgrade', [
                'trainee_cid' => $traineeCid,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getLastGermanSession(int $vatsimId): ?Carbon
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->get("{$this->baseUrl}/facility/user/{$vatsimId}/last-session");

            if (! $response->successful()) {
                Log::warning('Failed to fetch last German session', [
                    'vatsim_id' => $vatsimId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();
            $date = $data['data']['last_session'] ?? $data['last_session'] ?? null;

            return $date ? Carbon::parse($date) : null;
        } catch (\Throwable $e) {
            Log::error('Exception fetching last German session', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getRoster(): array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->get("{$this->baseUrl}/facility/roster");

            if (! $response->successful()) {
                Log::error('Failed to fetch roster', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();
            $raw = $data['data']['controllers'] ?? [];

            return array_values(array_filter(array_map(
                fn ($entry) => is_numeric($entry) ? (int) $entry : null,
                $raw,
            )));
        } catch (\Throwable $e) {
            Log::error('Error fetching roster', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function sortByPosition(array $endorsements): array
    {
        usort($endorsements, fn ($a, $b) => strcmp(
            $this->positionSortKey($a->position),
            $this->positionSortKey($b->position),
        ));

        return $endorsements;
    }

    private function positionSortKey(string $position): string
    {
        if ($position === '') {
            return '999_UNKNOWN';
        }

        if (str_ends_with($position, '_CTR')) {
            return '0_CTR_'.substr($position, 0, -4);
        }

        $parts = explode('_', $position);

        if (count($parts) >= 2) {
            $priority = match (implode('_', array_slice($parts, 1))) {
                'APP' => 1,
                'TWR' => 2,
                'GNDDEL' => 3,
                default => 9,
            };

            return sprintf('1_%s_%02d', $parts[0], $priority);
        }

        return "9_{$position}";
    }
}
