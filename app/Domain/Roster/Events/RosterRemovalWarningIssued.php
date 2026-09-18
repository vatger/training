<?php

namespace App\Domain\Roster\Events;

use App\Models\RosterEntry;

readonly class RosterRemovalWarningIssued
{
    public function __construct(
        public int $vatsimId,
        public RosterEntry $entry,
    ) {}
}
