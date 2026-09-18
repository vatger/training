<?php

namespace App\Domain\Endorsement\Events;

use App\Models\User;

readonly class Tier1EndorsementGranted
{
    public function __construct(
        public string $position,
        public User $trainee,
        public User $mentor,
    ) {}
}
