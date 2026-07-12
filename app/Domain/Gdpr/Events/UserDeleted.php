<?php

namespace App\Domain\Gdpr\Events;

use App\Models\User;

readonly class UserDeleted
{
    public function __construct(
        public User   $user,
        public string $ipAddress,
    ) {}
}
