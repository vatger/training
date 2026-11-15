<?php

namespace App\Policies\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'users';
    }

    public function create(User $user): bool
    {
        return false;
    }

    // Anonymised users have no VATSIM ID, which the edit page is routed by.
    public function view(User $user, Model $record): bool
    {
        return filled($record->vatsim_id) && parent::view($user, $record);
    }

    public function update(User $user, Model $record): bool
    {
        return filled($record->vatsim_id) && parent::update($user, $record);
    }
}
