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
        'removal_date',
        'removal_notified',
        'created_at_vateud',
    ];

    protected $casts = [
        'activity_minutes' => 'float',
        'last_updated' => 'datetime',
        'last_activity_date' => 'date',
        'removal_date' => 'date',
        'removal_notified' => 'boolean',
        'created_at_vateud' => 'datetime',
    ];

    /**
     * Get activity in hours
     */
    public function getActivityHoursAttribute(): float
    {
        return round($this->activity_minutes / 60, 2);
    }

    /**
     * Check if endorsement is eligible for removal
     */
    public function isEligibleForRemoval(int $daysDelta = 180): bool
    {
        $minMinutes = config('services.vateud.min_activity_minutes', 180);
        $noMinHours = $this->activity_minutes < $minMinutes;
        $daysAgo = Carbon::now()->subDays($daysDelta);
        $notRecent = $this->created_at_vateud < $daysAgo;

        return $noMinHours && $notRecent;
    }

    /**
     * Get activity status
     */
    public function getStatusAttribute(): string
    {
        $minMinutes = config('services.vateud.min_activity_minutes', 180);
        
        // If removal date is set and in the future, show removal status
        if ($this->removal_date && $this->removal_date->isFuture()) {
            return 'removal';
        } 
        // If activity meets minimum requirements
        elseif ($this->activity_minutes >= $minMinutes) {
            return 'active';
        } 
        // If activity is between 50% and 100% of minimum (warning zone)
        elseif ($this->activity_minutes >= ($minMinutes * 0.5)) {
            return 'warning';
        } 
        // If activity is below 50% of minimum (potential removal)
        else {
            return 'warning'; // Changed from 'removal' - only show removal if actually marked for removal
        }
    }

    /**
     * Get progress percentage
     */
    public function getProgressAttribute(): float
    {
        $minMinutes = config('services.vateud.min_activity_minutes', 180);
        return min(($this->activity_minutes / $minMinutes) * 100, 100);
    }

    /**
     * Scope for activities needing update
     */
    public function scopeNeedsUpdate($query)
    {
        return $query->orderBy('last_updated', 'asc');
    }

    /**
     * Scope for activities marked for removal
     */
    public function scopeMarkedForRemoval($query)
    {
        return $query->whereNotNull('removal_date')
            ->where('removal_date', '<', Carbon::now())
            ->where('removal_notified', true);
    }
}