<?php

namespace App\Domain\Cpt\Events;

use App\Models\Course;
use App\Models\Cpt;
use App\Models\User;

readonly class CptCreated
{
    public function __construct(
        public Cpt $cpt,
        public Course $course,
        public User $trainee,
        public User $creator,
        public ?User $examiner,
        public ?User $local,
    ) {}
}
