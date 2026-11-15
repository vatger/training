<?php

namespace App\Policies;

use App\Models\TrainingLog;
use App\Models\User;

class TrainingLogPolicy
{
    /**
     * Determine if the user can view any training logs.
     */
    public function viewAny(User $user): bool
    {
        // The public log pages filter by participation themselves and never ask for
        // this, so it only gates the admin panel's training log list.
        return $user->canAccessAdminResource('training_logs');
    }

    /**
     * Determine if the user can view the training log.
     */
    public function view(User $user, TrainingLog $log): bool
    {
        // User can view if they are:
        // - The trainee
        // - The mentor who created the log
        // - A mentor for the course
        // - A superuser/admin

        if ($user->canAccessAdminResource('training_logs')) {
            return true;
        }

        if ($user->id === $log->trainee_id) {
            return true;
        }

        if ($user->id === $log->mentor_id) {
            return true;
        }

        if ($log->course && $user->isMentorForCourse($log->course)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create training logs.
     */
    public function create(User $user): bool
    {
        return $user->isMentor() || $user->isChiefOfTraining() || $user->isLeadingMentor() || $user->canEditAdminResource('training_logs');
    }

    /**
     * Determine if the user can update the training log.
     */
    public function update(User $user, TrainingLog $log): bool
    {
        // Superuser/admin always can
        if ($user->is_superuser || $user->is_admin) {
            return true;
        }

        // Original mentor who created the log can edit
        if ($user->id === $log->mentor_id) {
            // Check if user is still a mentor for the course
            if ($log->course) {
                return $user->isMentorForCourse($log->course);
            }

            return true;
        }

        return $user->canEditTrainingLog($log) || $user->canEditAdminResource('training_logs');
    }

    /**
     * Determine if the user can delete the training log.
     */
    public function delete(User $user, TrainingLog $log): bool
    {
        // Only the mentor who created the log or superusers can delete
        return $user->id === $log->mentor_id || $user->is_superuser || $user->is_admin;
    }

    /**
     * Determine if the user can bulk delete training logs (admin panel only).
     */
    public function deleteAny(User $user): bool
    {
        return $user->is_superuser || $user->is_admin;
    }

    /**
     * Determine if the user can view internal remarks.
     */
    public function viewInternal(User $user, TrainingLog $log): bool
    {
        // User can view internal remarks if they are:
        // - The mentor who created the log
        // - A mentor for the course
        // - A superuser/admin

        if ($user->is_superuser || $user->is_admin) {
            return true;
        }

        if ($user->id === $log->mentor_id) {
            return true;
        }

        if ($log->course && $user->isMentorForCourse($log->course)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can export the training log.
     */
    public function export(User $user, TrainingLog $log): bool
    {
        // Same as view permission
        return $this->view($user, $log);
    }
}
