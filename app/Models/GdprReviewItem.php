<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprReviewItem extends Model
{
    public const REASON_AUTHOR = 'author';

    public const REASON_MENTION = 'mention';

    protected $fillable = [
        'gdpr_removal_request_id',
        'table_name',
        'record_id',
        'label',
        'reason',
        'reviewed_at',
        'reviewed_by_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(GdprRemovalRequest::class, 'gdpr_removal_request_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }
}
