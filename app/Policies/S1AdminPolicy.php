<?php

namespace App\Policies\S1;

use App\Models\User;

class S1AdminPolicy
{
    public function administerS1(User $user): bool
    {
        return $user->hasAnyRole(['cos', 'mentor']);
    }

    public function viewMentorStats(User $user): bool
    {
        return $user->hasRole('cos');
    }

    public function viewS1Comments(User $user): bool
    {
        return $user->hasAnyRole(['cos', 'mentor']);
    }
}