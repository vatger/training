<?php

namespace App\Domain\Training\Events;

use App\Models\Course;
use App\Models\User;

readonly class FamiliarisationAdded
{
    public function __construct(
        public User $trainee,
        public string $sectorName,
        public int $sectorId,
        public string $fir,
        public User $mentor,
        public Course $course,
    ) {}
}
