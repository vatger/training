<?php

namespace App\Policies\Admin;

use App\Models\User;

class CptPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'cpts';
    }

    public function create(User $user): bool
    {
        return false;
    }
}
