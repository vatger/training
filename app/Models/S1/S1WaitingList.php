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

    const INACTIVITY_DAYS = 31;                             // Days without page visit = inactive
    const WARNING_DAYS_BEFORE_EXPIRY = 7;                   // Warning 7 days before removal
    const MODULE_2_MAX_INACTIVITY_DAYS = 31;                // Days without Module 2 quiz activity
    const NEXT_MODULE_SIGNUP_DEADLINE_DAYS = 31;            // Days to join next module

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
        if (!$this->last_confirmed_at) {
            return false;
        }

        $daysSinceLastVisit = now()->diffInDays($this->last_confirmed_at, false);
        return $daysSinceLastVisit > self::INACTIVITY_DAYS;
    }

    public function isApproachingExpiry(): bool
    {
        if (!$this->last_confirmed_at) {
            return false;
        }

        $daysSinceLastVisit = now()->diffInDays($this->last_confirmed_at, false);
        $daysUntilExpiry = self::INACTIVITY_DAYS - $daysSinceLastVisit;

        return $daysUntilExpiry <= self::WARNING_DAYS_BEFORE_EXPIRY && $daysUntilExpiry > 0;
    }

    public function isApproachingConfirmationDeadline(): bool
    {
        return $this->isApproachingExpiry();
    }

    public function needsActivityWarning(): bool
    {
        return $this->isApproachingExpiry() && !$this->activity_warning_sent_at;
    }

    public function confirm(): void
    {
        $this->update([
            'last_confirmed_at' => now(),
            'confirmation_due_at' => now()->addDays(self::INACTIVITY_DAYS),
            'activity_warning_sent_at' => null,
            'confirmation_reminders_sent' => 0,
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'last_confirmed_at' => now(),
            'confirmation_due_at' => now()->addDays(self::INACTIVITY_DAYS),
            'expires_at' => null, // Clear old expiry
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
            ->where('last_confirmed_at', '<=', now()->subDays(self::INACTIVITY_DAYS));
    }

    public function scopeExpired($query)
    {
        return $query->where('is_active', true)
            ->where('last_confirmed_at', '<=', now()->subDays(self::INACTIVITY_DAYS));
    }

    public function scopeNeedingWarning($query)
    {
        $warningThreshold = now()->subDays(self::INACTIVITY_DAYS - self::WARNING_DAYS_BEFORE_EXPIRY);

        return $query->where('is_active', true)
            ->where('last_confirmed_at', '<=', $warningThreshold)
            ->where('last_confirmed_at', '>', now()->subDays(self::INACTIVITY_DAYS))
            ->whereNull('activity_warning_sent_at');
    }
}