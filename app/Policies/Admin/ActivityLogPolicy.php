<?php

namespace App\Policies\Admin;

use App\Models\ActivityLog;
use App\Models\User;

/** The activity log is read-only. */
class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminResource('activity_logs');
    }

    public function view(User $user, ActivityLog $log): bool
    {
        // A record of an admin-panel change is admin-only, even for someone who can
        // otherwise open this resource. The query scope already keeps these off the
        // list, but direct URL access has to be guarded too.
        if ($log->is_admin_action && ! $user->is_admin) {
            return false;
        }

        return $user->canAccessAdminResource('activity_logs');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ActivityLog $log): bool
    {
        return false;
    }

    public function delete(User $user, ActivityLog $log): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
