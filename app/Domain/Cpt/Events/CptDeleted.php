<?php

namespace App\Domain\Cpt\Events;

use App\Models\Cpt;
use App\Models\Course;
use App\Models\User;

readonly class CptDeleted
{
    public function __construct(
        public Cpt    $cpt,
        public Course $course,
        public User   $trainee,
        public User   $deleter,
    ) {}
}