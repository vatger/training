<?php

namespace App\Models\S1;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class S1SessionSignup extends Model
{
    protected $table = 's1_session_signups';

    protected $fillable = [
        'session_id',
        'user_id',
        'waiting_list_id',
        'signed_up_at',
        'was_selected',
        'selected_at',
        'notification_sent',
    ];

    protected $casts = [
        'signed_up_at' => 'datetime',
        'selected_at' => 'datetime',
        'was_selected' => 'boolean',
        'notification_sent' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(S1Session::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function waitingList(): BelongsTo
    {
        return $this->belongsTo(S1WaitingList::class, 'waiting_list_id');
    }

    public function markAsSelected(): void
    {
        $this->update([
            'was_selected' => true,
            'selected_at' => now(),
        ]);
    }

    public function scopeSelected($query)
    {
        return $query->where('was_selected', true);
    }

    public function scopeUnselected($query)
    {
        return $query->where('was_selected', false);
    }

    public function scopeNeedingNotification($query)
    {
        return $query->where('was_selected', true)
            ->where('notification_sent', false);
    }
}