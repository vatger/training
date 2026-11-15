<?php

namespace App\Policies\Admin;

use App\Models\GdprRemovalRequest;
use App\Models\User;

/**
 * Requests are created through the API and only ever processed via the approve and review
 * actions on the view page, never edited or deleted directly.
 */
class GdprRemovalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canReviewGdprRequests();
    }

    public function view(User $user, GdprRemovalRequest $request): bool
    {
        return $user->canReviewGdprRequests();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, GdprRemovalRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, GdprRemovalRequest $request): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
