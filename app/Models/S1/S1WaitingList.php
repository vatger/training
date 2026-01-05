<?php

namespace App\Models\S1;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class S1WaitingList extends Model
{
    protected $table = 's1_waiting_lists';

    protected $fillable = [
        'user_id',
        'module_id',
        'joined_at',
        'last_confirmed_at',
        'confirmation_due_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_confirmed_at' => 'datetime',
        'confirmation_due_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(S1Module::class, 'module_id');
    }

    public function signups(): HasMany
    {
        return $this->hasMany(S1SessionSignup::class, 'waiting_list_id');
    }

    public function getPositionInQueueAttribute(): int
    {
        return static::where('module_id', $this->module_id)
            ->where('is_active', true)
            ->where('joined_at', '<', $this->joined_at)
            ->count() + 1;
    }

    public function needsConfirmation(): bool
    {
        if (!$this->confirmation_due_at) {
            return false;
        }

        return $this->confirmation_due_at->isPast();
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    public function confirm(): void
    {
        $this->update([
            'last_confirmed_at' => now(),
            'confirmation_due_at' => now()->addDays(config('s1.waiting_list_confirmation_days', 30)),
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNeedingConfirmation($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('confirmation_due_at')
            ->where('confirmation_due_at', '<=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}