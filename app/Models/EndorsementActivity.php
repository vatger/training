<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class EndorsementActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'endorsement_id',
        'vatsim_id',
        'position',
        'activity_minutes',
        'last_updated',
        'last_activity_date',
        'low_activity_since',
        'removal_date',
        'removal_notified',
        'created_at_vateud',
    ];

    protected $casts = [
        'activity_minutes' => 'float',
        'last_updated' => 'datetime',
        'last_activity_date' => 'date',
        'low_activity_since' => 'date',
        'removal_date' => 'date',
        'removal_notified' => 'boolean',
        'created_at_vateud' => 'datetime',
    ];

    public function getActivityHoursAttribute(): float
    {
        return round($this->activity_minutes / 60, 2);
    }

    public function isEligibleForRemoval(int $daysDelta = 180): bool
    {
        $minMinutes = config('services.vateud.min_activity_minutes', 180);
        $noMinHours = $this->activity_minutes < $minMinutes;
        $daysAgo = Carbon::now()->subDays($daysDelta);
        $notRecent = $this->created_at_vateud < $daysAgo;

        return $noMinHours && $notRecent;
    }

    public function getStatusAttribute(): string
    {
        $minMinutes = config('services.vateud.min_activity_minutes', 180);

        if ($this->removal_date && $this->removal_date->isFuture()) {
            return 'removal';
        } elseif ($this->activity_minutes >= $minMinutes) {
            return 'active';
        } elseif ($this->activity_minutes >= ($minMinutes * 0.5)) {
            return 'warning';
        } else {
            return 'warning';
        }
    }

    public function getProgressAttribute(): float
    {
        $minMinutes = config('services.vateud.min_activity_minutes', 180);
        return min(($this->activity_minutes / $minMinutes) * 100, 100);
    }

    public function scopeNeedsUpdate($query)
    {
        return $query->orderBy('last_updated', 'asc');
    }

    public function scopeMarkedForRemoval($query)
    {
        return $query->whereNotNull('removal_date')
            ->where('removal_date', '<', Carbon::now())
            ->where('removal_notified', true);
    }
}