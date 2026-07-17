<?php

namespace App\Domain\Cpt\Events;

use App\Models\Cpt;
use App\Models\User;

readonly class CptExaminerJoined
{
    public function __construct(
        public Cpt  $cpt,
        public User $examiner,
    ) {}
}