<?php

namespace App\Integrations\VatEud\DTOs;

use Carbon\Carbon;

readonly class Tier2EndorsementData
{
    public function __construct(
        public int    $id,
        public int    $userCid,
        public string $position,
        public int    $facility,
        public Carbon $createdAt,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            id:        $data['id'],
            userCid:   $data['user_cid'],
            position:  $data['position'],
            facility:  $data['facility'],
            createdAt: Carbon::parse($data['created_at']),
        );
    }
}