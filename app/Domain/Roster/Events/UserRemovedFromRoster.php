<?php

namespace App\Domain\Roster\Events;

readonly class UserRemovedFromRoster
{
    public function __construct(
        public int $vatsimId,
    ) {}
}
