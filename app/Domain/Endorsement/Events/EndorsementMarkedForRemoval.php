<?php

namespace App\Domain\Endorsement\Events;

use App\Models\EndorsementActivity;
use App\Models\User;

readonly class EndorsementMarkedForRemoval
{
    public function __construct(
        public EndorsementActivity $activity,
        public User $actor,
        public ?User $trainee,
    ) {}
}