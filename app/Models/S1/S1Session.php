<?php

namespace App\Models\S1;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class S1Session extends Model
{
    protected $table = 's1_sessions';

    protected $fillable = [
        'module_id',
        'mentor_id',
        'scheduled_at',
        'max_trainees',
        'language',
        'signups_open',
        'signups_locked',
        'signups_lock_at',
        'attendance_completed',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'signups_lock_at' => 'datetime',
        'signups_open' => 'boolean',
        'signups_locked' => 'boolean',
        'attendance_completed' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($session) {
            if (!$session->signups_lock_at) {
                $session->signups_lock_at = Carbon::parse($session->scheduled_at)->subHours(48);
            }
        });
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(S1Module::class, 'module_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function signups(): HasMany
    {
        return $this->hasMany(S1SessionSignup::class, 'session_id');
    }

    public function selectedSignups(): HasMany
    {
        return $this->signups()->where('was_selected', true);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(S1SessionAttendance::class, 'session_id');
    }

    public function shouldLockSignups(): bool
    {
        if ($this->signups_locked) {
            return false;
        }

        return $this->signups_lock_at && $this->signups_lock_at->isPast();
    }

    public function lockSignups(): void
    {
        if ($this->signups_locked) {
            return;
        }

        $this->update(['signups_locked' => true]);
    }

    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->max_trainees - $this->selectedSignups()->count());
    }

    public function getTotalSignupsAttribute(): int
    {
        return $this->signups()->count();
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now());
    }

    public function scopeUnlocked($query)
    {
        return $query->where('signups_locked', false);
    }

    public function scopeNeedingLock($query)
    {
        return $query->where('signups_locked', false)
            ->whereNotNull('signups_lock_at')
            ->where('signups_lock_at', '<=', now());
    }
}