<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GdprRemovalRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    /** User data is deleted, remaining records are waiting for manual review. */
    public const STATUS_APPROVED = 'approved';

    public const STATUS_COMPLETED = 'completed';

    /** The homepage stopped polling before the request was approved. */
    public const STATUS_CANCELLED = 'cancelled';

    /** Days without a poll after which a pending request is cancelled. */
    public const STALE_AFTER_DAYS = 3;

    /** Days after which a pending request of a user without any special data is approved automatically. */
    public const AUTO_REMOVE_AFTER_DAYS = 2;

    protected $fillable = [
        'user_id',
        'vatsim_id',
        'user_name',
        'search_terms',
        'last_polled_at',
        'status',
        'approved_by_id',
        'approved_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected $casts = [
        'vatsim_id' => 'integer',
        'search_terms' => 'array',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_polled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function reviewItems(): HasMany
    {
        return $this->hasMany(GdprReviewItem::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    /** Pending requests that have not been polled for STALE_AFTER_DAYS. */
    public function scopeStale($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('last_polled_at', '<=', now()->subDays(self::STALE_AFTER_DAYS));
    }

    /** Pending requests old enough to be removed automatically, if the user has no special data. */
    public function scopeDueForAutoRemoval($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('created_at', '<=', now()->subDays(self::AUTO_REMOVE_AFTER_DAYS));
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}
