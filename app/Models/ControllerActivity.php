<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The calculated activity of one controller on one login station over a rolling
 * window. Read by Filament, the endorsement retention flow and any other
 * feature; never written outside the activity engine.
 */
class ControllerActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'cid',
        'station_logon',
        'window_days',
        'minutes',
        'last_session_at',
        'eligible_since',
        'breakdown',
        'calculated_at',
    ];

    protected $casts = [
        'cid' => 'integer',
        'window_days' => 'integer',
        'minutes' => 'float',
        'last_session_at' => 'datetime',
        'eligible_since' => 'datetime',
        'breakdown' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function getHoursAttribute(): float
    {
        return round($this->minutes / 60, 2);
    }

    public function isActive(): bool
    {
        return $this->minutes >= (int) config('activity.min_minutes');
    }

    public function scopeForStation($query, string $logon)
    {
        return $query->where('station_logon', strtoupper($logon));
    }

    public function scopeForController($query, int $cid)
    {
        return $query->where('cid', $cid);
    }
}
