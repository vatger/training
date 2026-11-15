<?php

namespace App\Policies\Admin;

use App\Models\User;

class EndorsementActivityPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'endorsement_activities';
    }

    public function create(User $user): bool
    {
        return false;
    }
}
