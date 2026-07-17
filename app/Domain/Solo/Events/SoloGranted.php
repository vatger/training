<?php

namespace App\Domain\Solo\Events;

use App\Models\Course;
use App\Models\User;

readonly class SoloGranted
{
    public function __construct(
        public Course $course,
        public User $trainee,
        public User $mentor,
        public string $position,
        public string $expiryDate,
    ) {}
}