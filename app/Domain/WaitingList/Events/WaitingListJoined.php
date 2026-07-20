<?php

namespace App\Domain\WaitingList\Events;

use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;

readonly class WaitingListJoined
{
    public function __construct(
        public WaitingListEntry $entry,
        public Course $course,
        public User $user,
    ) {}
}
