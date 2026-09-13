<?php

namespace App\Domain\Activity\Events;

readonly class ActivityReferenceDataUpdated
{
    public function __construct(
        public string $version,
        public int $files,
    ) {}
}
