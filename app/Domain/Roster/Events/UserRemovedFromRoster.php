<?php

namespace App\Domain\Roster\Events;

use Carbon\Carbon;

readonly class UserRemovedFromRoster
{
    public function __construct(
        public int $vatsimId,
        public ?Carbon $lastSession = null,
        public ?int $inactiveDays = null,
    ) {}
}
