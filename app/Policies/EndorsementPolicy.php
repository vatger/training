<?php

namespace App\Policies;

use App\Models\User;

class EndorsementPolicy
{
    /**
     * Determine if the user can manage endorsements
     */
    public function mentor(User $user): bool
    {
        return $user->isMentor() || $user->is_superuser;
    }

    /**
     * Determine if the user can view endorsement management
     */
    public function viewManagement(User $user): bool
    {
        return $this->mentor($user);
    }

    /**
     * Determine if the user can remove tier 1 endorsements
     */
    public function removeTier1(User $user): bool
    {
        return $this->mentor($user);
    }

    /**
     * Determine if the user can request tier 2 endorsements
     */
    public function requestTier2(User $user): bool
    {
        return $user->isVatsimUser();
    }

    /**
     * Determine if the user can view their own endorsements
     */
    public function viewOwn(User $user): bool
    {
        return $user->isVatsimUser() || $user->is_admin;
    }
}