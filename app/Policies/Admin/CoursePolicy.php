<?php

namespace App\Policies\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CoursePolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'courses';
    }

    public function viewAny(User $user): bool
    {
        return $user->isLeadingMentor() || parent::viewAny($user);
    }

    public function view(User $user, Model $record): bool
    {
        return $this->manageable($user, $record) ?? parent::view($user, $record);
    }

    public function create(User $user): bool
    {
        return $this->isAdminOrSuperuser($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->manageable($user, $record) ?? parent::update($user, $record);
    }

    /**
     * Leading mentors are limited to courses of their own FIR. Returns null when the
     * user is not a leading mentor and the permission-based checks should decide.
     */
    private function manageable(User $user, Model $record): ?bool
    {
        if ($this->isAdminOrSuperuser($user)) {
            return true;
        }

        if (! $user->isLeadingMentor() || ! $record->mentor_group_id) {
            return null;
        }

        $mentorGroupName = $record->mentorGroup?->name;
        $fir = $mentorGroupName ? $user->getFirFromMentorGroup($mentorGroupName) : null;

        return $fir !== null && $user->isLeadingMentorForFir($fir);
    }
}
