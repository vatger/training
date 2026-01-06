<?php

namespace App\Models\S1;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class S1UserBan extends Model
{
    protected $table = 's1_user_bans';

    protected $fillable = [
        'user_id',
        'reason',
        'banned_at',
        'expires_at',
        'banned_by_mentor_id',
        'is_active',
    ];

    protected $casts = [
        'banned_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bannedByMentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by_mentor_id');
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    public function isPermanent(): bool
    {
        return $this->expires_at === null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}

class S1TraineeComment extends Model
{
    protected $table = 's1_trainee_comments';

    protected $fillable = [
        'user_id',
        'author_id',
        'comment',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }
}

class S1ProgressReset extends Model
{
    protected $table = 's1_progress_resets';

    protected $fillable = [
        'user_id',
        'reset_by_mentor_id',
        'reason',
        'modules_reset',
        'moodle_data_backup',
        'reset_at',
    ];

    protected $casts = [
        'reset_at' => 'datetime',
        'modules_reset' => 'array',
        'moodle_data_backup' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resetByMentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reset_by_mentor_id');
    }
}