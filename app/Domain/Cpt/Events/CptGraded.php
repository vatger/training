<?php

namespace App\Domain\Cpt\Events;

use App\Models\Cpt;
use App\Models\User;

readonly class CptGraded
{
    public function __construct(
        public Cpt $cpt,
        public bool $passed,
        public User $grader,
    ) {}
}
