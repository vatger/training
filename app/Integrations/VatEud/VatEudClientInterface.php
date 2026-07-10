<?php

namespace App\Integrations\VatEud;

use App\Integrations\VatEud\DTOs\Tier1EndorsementData;
use App\Integrations\VatEud\DTOs\Tier2EndorsementData;
use App\Integrations\VatEud\DTOs\SoloEndorsementData;
use App\Integrations\VatEud\DTOs\UserExamsData;
use Carbon\Carbon;

interface VatEudClientInterface
{
    /** @return Tier1EndorsementData[] */
    public function getTier1Endorsements(): array;

    public function createTier1Endorsement(int $userCid, string $position, int $instructorCid): bool;

    public function deleteTier1Endorsement(int $endorsementId): bool;

    /** @return Tier2EndorsementData[] */
    public function getTier2Endorsements(): array;

    public function createTier2Endorsement(int $userCid, string $position, int $instructorCid): bool;

    public function deleteTier2Endorsement(int $endorsementId): bool;

    /** @return SoloEndorsementData[] */
    public function getSoloEndorsements(): array;

    public function createSoloEndorsement(int $userCid, string $position, string $expireAt, int $instructorCid): array;

    public function deleteSoloEndorsement(int $soloId): bool;

    public function removeRosterAndEndorsements(int $vatsimId): bool;

    public function getUserExams(int $vatsimId): UserExamsData;

    public function assignCoreTheoryTest(int $vatsimId, int $examId, int $instructorCid): array;

    public function uploadCptLog(
        int    $traineeCid,
        int    $examinerCid,
        string $position,
        string $note,
        bool   $cptPass,
        string $filePath,
    ): array;

    public function requestUpgrade(int $traineeCid, int $instructorCid, int $newRating): array;

    public function getLastGermanSession(int $vatsimId): ?Carbon;

    /** @return int[] */
    public function getRoster(): array;
}