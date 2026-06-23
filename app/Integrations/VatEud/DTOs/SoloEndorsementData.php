<?php

namespace App\Integrations\VatEud\DTOs;

use Carbon\Carbon;

readonly class SoloEndorsementData
{
    public function __construct(
        public int     $id,
        public int     $userCid,
        public string  $position,
        public int     $facility,
        public int $mentor,
        public Carbon  $expireAt,
        public Carbon  $createdAt,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            id:        $data['id'],
            userCid:   $data['user_cid'],
            position:  $data['position'],
            facility:  $data['facility'],
            mentor: $data['instructor_cid'],
            expireAt:  Carbon::parse($data['expire_at']),
            createdAt: Carbon::parse($data['created_at']),
        );
    }
}