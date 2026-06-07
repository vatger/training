<?php

namespace App\Domain\Cpt\Events;

use App\Models\Cpt;
use App\Models\CptLog;
use App\Models\User;

readonly class CptLogUploaded
{
    public function __construct(
        public CptLog $log,
        public Cpt    $cpt,
        public User   $uploader,
    ) {}
}