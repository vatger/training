<?php

namespace App\Domain\Endorsement\Events;

use App\Models\Tier2Endorsement;
use App\Models\User;

readonly class Tier2EndorsementGranted
{
    public function __construct(
        public Tier2Endorsement $tier2Endorsement,
        public User $trainee,
    ) {}
}