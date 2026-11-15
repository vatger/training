<?php

namespace App\Policies\Admin;

use App\Models\GdprReviewItem;
use App\Models\User;

/**
 * Review items are listed in the request's relation manager and only changed through its review action.
 */
class GdprReviewItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canReviewGdprRequests();
    }

    public function view(User $user, GdprReviewItem $item): bool
    {
        return $user->canReviewGdprRequests();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, GdprReviewItem $item): bool
    {
        return false;
    }

    public function delete(User $user, GdprReviewItem $item): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
