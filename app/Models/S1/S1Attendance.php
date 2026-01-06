<?php

namespace App\Models\S1;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class S1Attendance extends Model
{
    protected $table = 's1_session_attendances';

    const STATUS_ATTENDED = 'attended';
    const STATUS_ABSENT = 'absent';
    const STATUS_EXCUSED = 'excused';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'session_id',
        'user_id',
        'signup_id',
        'status',
        'notes',
        'marked_by_mentor_id',
        'marked_at',
        'spontaneous',
    ];

    protected $casts = [
        'marked_at' => 'datetime',
        'spontaneous' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(S1Session::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function signup(): BelongsTo
    {
        return $this->belongsTo(S1SessionSignup::class, 'signup_id');
    }

    public function markedByMentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_mentor_id');
    }

    public function shouldKeepWaitingListPosition(): bool
    {
        return in_array($this->status, [self::STATUS_EXCUSED, self::STATUS_PASSED]);
    }

    public function shouldLoseWaitingListPosition(): bool
    {
        return in_array($this->status, [self::STATUS_ABSENT, self::STATUS_FAILED]);
    }

    public function shouldCompleteModule(): bool
    {
        return $this->status === self::STATUS_PASSED;
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ATTENDED => 'Attended',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_EXCUSED => 'Excused',
            self::STATUS_PASSED => 'Passed',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
