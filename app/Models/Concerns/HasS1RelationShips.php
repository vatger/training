<?php

namespace App\Models\Concerns;

use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1WaitingList;
use App\Models\S1\S1Session;
use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1Attendance;
use App\Models\S1\S1UserBan;
use App\Models\S1\S1TraineeComment;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasS1Relationships
{
    public function s1ModuleCompletions(): HasMany
    {
        return $this->hasMany(S1ModuleCompletion::class, 'user_id');
    }

    public function s1CompletedByMentor(): HasMany
    {
        return $this->hasMany(S1ModuleCompletion::class, 'completed_by_mentor_id');
    }

    public function s1WaitingLists(): HasMany
    {
        return $this->hasMany(S1WaitingList::class, 'user_id');
    }

    public function s1ActiveWaitingLists(): HasMany
    {
        return $this->s1WaitingLists()->where('is_active', true);
    }

    public function s1MentorSessions(): HasMany
    {
        return $this->hasMany(S1Session::class, 'mentor_id');
    }

    public function s1SessionSignups(): HasMany
    {
        return $this->hasMany(S1SessionSignup::class, 'user_id');
    }

    public function s1Attendances(): HasMany
    {
        return $this->hasMany(S1Attendance::class, 'user_id');
    }

    public function s1Bans(): HasMany
    {
        return $this->hasMany(S1UserBan::class, 'user_id');
    }

    public function s1ActiveBan(): ?S1UserBan
    {
        return $this->s1Bans()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function s1TraineeComments(): HasMany
    {
        return $this->hasMany(S1TraineeComment::class, 'user_id');
    }

    public function s1AuthoredComments(): HasMany
    {
        return $this->hasMany(S1TraineeComment::class, 'author_id');
    }

    public function hasCompletedS1Module(int $moduleId): bool
    {
        return $this->s1ModuleCompletions()
            ->where('module_id', $moduleId)
            ->exists();
    }

    public function isOnS1WaitingList(int $moduleId): bool
    {
        return $this->s1ActiveWaitingLists()
            ->where('module_id', $moduleId)
            ->exists();
    }

    public function isBannedFromS1(): bool
    {
        return $this->s1ActiveBan() !== null;
    }
}