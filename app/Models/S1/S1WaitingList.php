<?php

namespace App\Models\S1;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class S1WaitingList extends Model
{
    use HasFactory;
    protected $table = 's1_waiting_lists';

    const CONFIRMATION_DAYS = 30;
    const EXPIRY_DAYS = 63;
    const WARNING_DAYS_BEFORE_EXPIRY = 7;
    const MODULE_2_MAX_INACTIVITY_DAYS = 40;
    const NEXT_MODULE_SIGNUP_DEADLINE_DAYS = 31;

    protected $fillable = [
        'user_id',
        'module_id',
        'joined_at',
        'last_confirmed_at',
        'confirmation_due_at',
        'expires_at',
        'is_active',
        'activity_warning_sent_at',
        'confirmation_reminders_sent',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_confirmed_at' => 'datetime',
        'confirmation_due_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'activity_warning_sent_at' => 'datetime',
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

    public function isApproachingExpiry(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        $daysUntilExpiry = now()->diffInDays($this->expires_at, false);
        return $daysUntilExpiry <= self::WARNING_DAYS_BEFORE_EXPIRY && $daysUntilExpiry > 0;
    }

    public function isApproachingConfirmationDeadline(): bool
    {
        if (!$this->confirmation_due_at) {
            return false;
        }

        $daysUntilConfirmation = now()->diffInDays($this->confirmation_due_at, false);
        return $daysUntilConfirmation <= self::WARNING_DAYS_BEFORE_EXPIRY && $daysUntilConfirmation > 0;
    }

    public function needsActivityWarning(): bool
    {
        return ($this->isApproachingExpiry() || $this->isApproachingConfirmationDeadline())
            && !$this->activity_warning_sent_at;
    }

    public function confirm(): void
    {
        $this->update([
            'last_confirmed_at' => now(),
            'confirmation_due_at' => now()->addDays(self::CONFIRMATION_DAYS),
            'activity_warning_sent_at' => null,
            'confirmation_reminders_sent' => 0,
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'last_confirmed_at' => now(),
            'confirmation_due_at' => now()->addDays(self::CONFIRMATION_DAYS),
            'expires_at' => now()->addDays(self::EXPIRY_DAYS)->startOfMonth(),
            'activity_warning_sent_at' => null,
            'confirmation_reminders_sent' => 0,
        ]);
    }

    public function markWarningAsSent(): void
    {
        $this->update([
            'activity_warning_sent_at' => now(),
            'confirmation_reminders_sent' => $this->confirmation_reminders_sent + 1,
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

    public function scopeNeedingWarning($query)
    {
        $warningDate = now()->addDays(self::WARNING_DAYS_BEFORE_EXPIRY);

        return $query->where('is_active', true)
            ->where(function ($q) use ($warningDate) {
                $q->where(function ($sq) use ($warningDate) {
                    $sq->whereNotNull('expires_at')
                        ->where('expires_at', '<=', $warningDate)
                        ->where('expires_at', '>', now());
                })
                    ->orWhere(function ($sq) use ($warningDate) {
                        $sq->whereNotNull('confirmation_due_at')
                            ->where('confirmation_due_at', '<=', $warningDate)
                            ->where('confirmation_due_at', '>', now());
                    });
            })
            ->whereNull('activity_warning_sent_at');
    }
}