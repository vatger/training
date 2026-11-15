<?php

namespace App\Domain\Gdpr\Events;

use App\Models\GdprRemovalRequest;
use App\Models\User;

readonly class UserDeleted
{
    public function __construct(
        public GdprRemovalRequest $request,
        public ?User $approver,
        public ?string $ipAddress,
    ) {}
}
