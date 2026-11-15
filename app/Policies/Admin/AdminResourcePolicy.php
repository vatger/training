<?php

namespace App\Policies\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy for the admin panel resources whose access is driven by the
 * `admin.{resource}.view` / `admin.{resource}.edit` permissions. Admins and
 * superusers pass every check through `canAccessAdminResource()` and
 * `canEditAdminResource()`; deleting is reserved for them.
 */
abstract class AdminResourcePolicy
{
    /** The permission key, e.g. `examiners` for `admin.examiners.view`. */
    abstract protected function resource(): string;

    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminResource($this->resource());
    }

    public function view(User $user, Model $record): bool
    {
        return $user->canAccessAdminResource($this->resource());
    }

    public function create(User $user): bool
    {
        return $user->canEditAdminResource($this->resource());
    }

    public function update(User $user, Model $record): bool
    {
        return $user->canEditAdminResource($this->resource());
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->isAdminOrSuperuser($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdminOrSuperuser($user);
    }

    protected function isAdminOrSuperuser(User $user): bool
    {
        return $user->is_admin || $user->is_superuser;
    }
}
