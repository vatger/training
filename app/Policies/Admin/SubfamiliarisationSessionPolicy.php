<?php

namespace App\Policies\Admin;

use App\Models\Subfamiliarisation;
use App\Models\SubfamiliarisationSession;
use App\Models\User;
use App\Services\SubfamiliarisationService;

class SubfamiliarisationSessionPolicy
{
    public function __construct(private readonly SubfamiliarisationService $service) {}

    public function viewAny(User $user): bool
    {
        return $user->canAccessAdminResource('familiarisations')
            || $this->isMentorOfAnySubfamiliarisation($user);
    }

    public function view(User $user, SubfamiliarisationSession $session): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdminOrSuperuser($user) || $this->isMentorOfAnySubfamiliarisation($user);
    }

    public function update(User $user, SubfamiliarisationSession $session): bool
    {
        return $this->isAdminOrSuperuser($user)
            || $this->service->canLogSession($user, $session->subfamiliarisation);
    }

    public function delete(User $user, SubfamiliarisationSession $session): bool
    {
        return $this->isAdminOrSuperuser($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdminOrSuperuser($user);
    }

    private function isAdminOrSuperuser(User $user): bool
    {
        return $user->is_admin || $user->is_superuser;
    }

    private function isMentorOfAnySubfamiliarisation(User $user): bool
    {
        return Subfamiliarisation::all()
            ->contains(fn (Subfamiliarisation $subfamiliarisation) => $this->service->canLogSession($user, $subfamiliarisation));
    }
}
