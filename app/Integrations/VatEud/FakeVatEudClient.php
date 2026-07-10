<?php

namespace App\Integrations\VatEud;

use App\Integrations\VatEud\DTOs\Tier1EndorsementData;
use App\Integrations\VatEud\DTOs\Tier2EndorsementData;
use App\Integrations\VatEud\DTOs\SoloEndorsementData;
use App\Integrations\VatEud\DTOs\UserExamsData;
use Carbon\Carbon;

class FakeVatEudClient implements VatEudClientInterface
{
    public function getTier1Endorsements(): array
    {
        return [
            Tier1EndorsementData::fromApiResponse([
                'id'         => 1,
                'user_cid'   => 1601613,
                'position'   => 'EDDL_TWR',
                'facility'   => 9,
                'created_at' => '2025-04-19T12:02:38.000000Z',
            ]),
        ];
    }

    public function createTier1Endorsement(int $userCid, string $position, int $instructorCid): bool
    {
        return true;
    }

    public function deleteTier1Endorsement(int $endorsementId): bool
    {
        return true;
    }

    public function getTier2Endorsements(): array
    {
        return [
            Tier2EndorsementData::fromApiResponse([
                'id'         => 25,
                'user_cid'   => 1441619,
                'position'   => 'EDXX_AFIS',
                'facility'   => 9,
                'created_at' => '2024-02-29T22:39:33.000000Z',
            ]),
        ];
    }

    public function createTier2Endorsement(int $userCid, string $position, int $instructorCid): bool
    {
        return true;
    }

    public function deleteTier2Endorsement(int $endorsementId): bool
    {
        return true;
    }

    public function getSoloEndorsements(): array
    {
        return [];
    }

    public function createSoloEndorsement(int $userCid, string $position, string $expireAt, int $instructorCid): array
    {
        return ['success' => true];
    }

    public function deleteSoloEndorsement(int $soloId): bool
    {
        return true;
    }

    public function removeRosterAndEndorsements(int $vatsimId): bool
    {
        return true;
    }

    public function getUserExams(int $vatsimId): UserExamsData
    {
        return UserExamsData::fromApiResponse([]);
    }

    public function assignCoreTheoryTest(int $vatsimId, int $examId, int $instructorCid): array
    {
        return ['success' => true, 'message' => 'Test assigned (fake)'];
    }

    public function uploadCptLog(
        int    $traineeCid,
        int    $examinerCid,
        string $position,
        string $note,
        bool   $cptPass,
        string $filePath,
    ): array {
        return ['success' => true];
    }

    public function requestUpgrade(int $traineeCid, int $instructorCid, int $newRating): array
    {
        return ['success' => true];
    }

    public function getLastGermanSession(int $vatsimId): ?Carbon
    {
        return Carbon::now()->subDays(10);
    }

    public function getRoster(): array
    {
        return [1601613, 1441619];
    }
}