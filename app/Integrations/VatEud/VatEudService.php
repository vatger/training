<?php

namespace App\Integrations\VatEud;

use App\Integrations\VatEud\DTOs\SoloEndorsementData;
use App\Integrations\VatEud\DTOs\Tier1EndorsementData;
use App\Integrations\VatEud\DTOs\Tier2EndorsementData;
use App\Integrations\VatEud\DTOs\UserExamsData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class VatEudService
{
    private const CACHE_TIER1 = 'vateud:tier1_endorsements';

    private const CACHE_TIER2 = 'vateud:tier2_endorsements';

    private const CACHE_SOLO = 'vateud:solo_endorsements';

    private const TTL_STANDARD = 10;

    private const TTL_SOLO = 5;

    public function __construct(
        private readonly VatEudClientInterface $client,
    ) {}

    /** @return Tier1EndorsementData[] */
    public function getTier1Endorsements(): array
    {
        return Cache::remember(
            self::CACHE_TIER1,
            now()->addMinutes(self::TTL_STANDARD),
            fn () => $this->client->getTier1Endorsements(),
        );
    }

    public function createTier1Endorsement(int $userCid, string $position, int $instructorCid): bool
    {
        $result = $this->client->createTier1Endorsement($userCid, $position, $instructorCid);

        if ($result) {
            Cache::forget(self::CACHE_TIER1);
        }

        return $result;
    }

    public function deleteTier1Endorsement(int $endorsementId): bool
    {
        $result = $this->client->deleteTier1Endorsement($endorsementId);

        if ($result) {
            Cache::forget(self::CACHE_TIER1);
        }

        return $result;
    }

    /** @return Tier2EndorsementData[] */
    public function getTier2Endorsements(): array
    {
        return Cache::remember(
            self::CACHE_TIER2,
            now()->addMinutes(self::TTL_STANDARD),
            fn () => $this->client->getTier2Endorsements(),
        );
    }

    public function createTier2Endorsement(int $userCid, string $position, int $instructorCid): bool
    {
        $result = $this->client->createTier2Endorsement($userCid, $position, $instructorCid);

        if ($result) {
            Cache::forget(self::CACHE_TIER2);
        }

        return $result;
    }

    public function deleteTier2Endorsement(int $endorsementId): bool
    {
        $result = $this->client->deleteTier2Endorsement($endorsementId);

        if ($result) {
            Cache::forget(self::CACHE_TIER2);
        }

        return $result;
    }

    /** @return SoloEndorsementData[] */
    public function getSoloEndorsements(): array
    {
        return Cache::remember(
            self::CACHE_SOLO,
            now()->addMinutes(self::TTL_SOLO),
            fn () => $this->client->getSoloEndorsements(),
        );
    }

    public function createSoloEndorsement(int $userCid, string $position, string $expireAt, int $instructorCid): array
    {
        $result = $this->client->createSoloEndorsement($userCid, $position, $expireAt, $instructorCid);

        if ($result['success']) {
            Cache::forget(self::CACHE_SOLO);
        }

        return $result;
    }

    public function deleteSoloEndorsement(int $soloId): bool
    {
        $result = $this->client->deleteSoloEndorsement($soloId);

        if ($result) {
            Cache::forget(self::CACHE_SOLO);
        }

        return $result;
    }

    public function removeRosterAndEndorsements(int $vatsimId): bool
    {
        $result = $this->client->removeRosterAndEndorsements($vatsimId);

        if ($result) {
            Cache::forget(self::CACHE_TIER1);
            Cache::forget(self::CACHE_TIER2);
        }

        return $result;
    }

    public function getUserExams(int $vatsimId): UserExamsData
    {
        return $this->client->getUserExams($vatsimId);
    }

    public function assignCoreTheoryTest(int $vatsimId, int $examId, int $instructorCid): array
    {
        return $this->client->assignCoreTheoryTest($vatsimId, $examId, $instructorCid);
    }

    public function uploadCptLog(
        int $traineeCid,
        int $examinerCid,
        string $position,
        string $note,
        bool $cptPass,
        string $filePath,
    ): array {
        return $this->client->uploadCptLog($traineeCid, $examinerCid, $position, $note, $cptPass, $filePath);
    }

    public function requestUpgrade(int $traineeCid, int $instructorCid, int $newRating): array
    {
        return $this->client->requestUpgrade($traineeCid, $instructorCid, $newRating);
    }

    public function getLastGermanSession(int $vatsimId): ?Carbon
    {
        return $this->client->getLastGermanSession($vatsimId);
    }

    public function refreshEndorsementCache(): void
    {
        Cache::forget(self::CACHE_TIER1);
        Cache::forget(self::CACHE_TIER2);
        Cache::forget(self::CACHE_SOLO);

        $this->getTier1Endorsements();
        $this->getTier2Endorsements();
        $this->getSoloEndorsements();
    }
}
