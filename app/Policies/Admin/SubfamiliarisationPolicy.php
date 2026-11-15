<?php

namespace App\Policies\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SubfamiliarisationPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'familiarisations';
    }

    // Viewing follows the familiarisations permission, but managing subfamiliarisations is admin-only.
    public function create(User $user): bool
    {
        return $this->isAdminOrSuperuser($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->isAdminOrSuperuser($user);
    }
}
