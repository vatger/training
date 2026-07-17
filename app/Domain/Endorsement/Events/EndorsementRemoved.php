<?php

namespace App\Domain\Endorsement\Events;

use App\Models\EndorsementActivity;

readonly class EndorsementRemoved
{
    public function __construct(
        public EndorsementActivity $activity,
        public float $activityMinutes,
    ) {}
}
