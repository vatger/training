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

    protected $fillable = [
        'user_id',
        'vatsim_id',
        'user_name',
        'search_terms',
        'status',
        'approved_by_id',
        'approved_at',
        'completed_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected $casts = [
        'vatsim_id' => 'integer',
        'search_terms' => 'array',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
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
}
