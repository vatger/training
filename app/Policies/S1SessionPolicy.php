<?php

namespace App\Policies\S1;

use App\Models\S1\S1Session;
use App\Models\User;

class S1SessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['cos', 'mentor', 'trainee']);
    }

    public function view(User $user, S1Session $session): bool
    {
        if ($user->hasRole('cos') || $user->hasRole('mentor')) {
            return true;
        }

        $hoursUntilSession = now()->diffInHours($session->scheduled_at, false);
        return $hoursUntilSession <= 48;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['cos', 'mentor']);
    }

    public function update(User $user, S1Session $session): bool
    {
        return $user->hasRole('cos') || $user->id === $session->mentor_id;
    }

    public function delete(User $user, S1Session $session): bool
    {
        return $user->hasRole('cos') || $user->id === $session->mentor_id;
    }

    public function viewAttendance(User $user, S1Session $session): bool
    {
        if ($user->hasRole('cos')) {
            return true;
        }

        if ($user->hasRole('mentor') && $user->id === $session->mentor_id) {
            return true;
        }

        return false;
    }

    public function markAttendance(User $user, S1Session $session): bool
    {
        if ($user->hasRole('cos')) {
            return true;
        }

        if ($user->hasRole('mentor') && $user->id === $session->mentor_id) {
            return true;
        }

        return false;
    }
}