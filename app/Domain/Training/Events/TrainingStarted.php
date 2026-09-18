<?php

namespace App\Domain\Training\Events;

use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;

readonly class TrainingStarted
{
    public function __construct(
        public Course $course,
        public User $trainee,
        public User $mentor,
        public WaitingListEntry $entry,
    ) {}
}
