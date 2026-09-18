<?php

namespace App\Domain\WaitingList\Events;

use App\Models\User;
use Illuminate\Support\Collection;

readonly class WaitingListVerificationRequested
{
    public function __construct(
        public User $user,
        public Collection $entries,
    ) {}
}
