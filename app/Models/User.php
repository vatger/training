<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;


class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vatsim_id',
        'first_name',
        'last_name',
        'email',
        'subdivision',
        'rating',
        'last_rating_change',
        'is_staff',
        'is_superuser',
        'is_admin',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_rating_change' => 'datetime',
        'is_staff' => 'boolean',
        'is_superuser' => 'boolean',
        'is_admin' => 'boolean',
        'rating' => 'integer',
        'vatsim_id' => 'integer',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the route key for the model.
     * This makes the model use vatsim_id for route model binding instead of id
     */
    public function getRouteKeyName()
    {
        return 'vatsim_id';
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the name attribute for compatibility
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Check if user is a mentor (has any mentor role)
     */
    public function isMentor(): bool
    {
        return $this->hasAnyRole(['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor', 'ATD Leitung', 'VATGER Leitung']);
    }

    /**
     * Check if user is a superuser
     */
    public function isSuperuser(): bool
    {
        return $this->is_superuser === true;
    }

    /**
     * Check if user is ATD or VATGER leadership
     */
    public function isLeadership(): bool
    {
        return $this->hasAnyRole(['ATD Leitung', 'VATGER Leitung']);
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Many-to-many relationship with roles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Scope to filter mentors
     */
    public function scopeMentors($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor']);
        });
    }

    /**
     * Scope to filter leadership
     */
    public function scopeLeadership($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['ATD Leitung', 'VATGER Leitung']);
        });
    }

    /**
     * Check if user is an admin account (for development/emergency access)
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Check if user is a VATSIM user (has vatsim_id)
     */
    public function isVatsimUser(): bool
    {
        return !empty($this->vatsim_id);
    }

    /**
     * Scope to get only admin accounts
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope to get only VATSIM users
     */
    public function scopeVatsimUsers($query)
    {
        return $query->whereNotNull('vatsim_id');
    }

    /**
     * Get user's endorsement activities
     */
    public function endorsementActivities()
    {
        return $this->hasMany(EndorsementActivity::class, 'vatsim_id', 'vatsim_id');
    }

    /**
     * Check if user has any active Tier 1 endorsements
     */
    public function hasActiveTier1Endorsements(): bool
    {
        if (!$this->isVatsimUser()) {
            return false;
        }

        return $this->endorsementActivities()
            ->where('activity_minutes', '>=', config('services.vateud.min_activity_minutes', 180))
            ->exists();
    }

    /**
     * Get user's endorsement summary
     */
    public function getEndorsementSummary(): array
    {
        if (!$this->isVatsimUser()) {
            return [
                'tier1_count' => 0,
                'tier2_count' => 0,
                'solo_count' => 0,
                'low_activity_count' => 0,
            ];
        }

        $tier1Count = $this->endorsementActivities()->count();
        $minRequiredMinutes = config('services.vateud.min_activity_minutes', 180);
        $lowActivityCount = $this->endorsementActivities()
            ->where('activity_minutes', '<', $minRequiredMinutes)
            ->count();

        // Note: Tier 2 and Solo counts would need to be fetched from VatEUD
        // This is a simplified version for the model
        return [
            'tier1_count' => $tier1Count,
            'tier2_count' => 0, // Would need VatEUD service call
            'solo_count' => 0,  // Would need VatEUD service call
            'low_activity_count' => $lowActivityCount,
        ];
    }

    /**
     * Check if user needs attention for endorsements (low activity, removal warnings, etc.)
     */
    public function needsEndorsementAttention(): bool
    {
        if (!$this->isVatsimUser()) {
            return false;
        }

        return $this->endorsementActivities()
            ->where(function ($query) {
                $minRequiredMinutes = config('services.vateud.min_activity_minutes', 180);
                $query->where('activity_minutes', '<', $minRequiredMinutes)
                    ->orWhereNotNull('removal_date');
            })
            ->exists();
    }

    /**
     * Get courses where user is an active trainee
     */
    public function activeCourses()
    {
        return $this->belongsToMany(Course::class, 'course_trainees');
    }

    /**
     * Get courses where user is a mentor
     */
    public function mentorCourses()
    {
        return $this->belongsToMany(Course::class, 'course_mentors');
    }

    /**
     * Get active rating courses only
     */
    public function activeRatingCourses()
    {
        return $this->activeCourses()->where('type', 'RTG');
    }

    /**
     * Get waiting list entries for this user
     */
    public function waitingListEntries()
    {
        return $this->hasMany(WaitingListEntry::class);
    }

    /**
     * Get familiarisations for this user
     */
    public function familiarisations()
    {
        return $this->hasMany(Familiarisation::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_superuser;
    }

    /**
     * Get training logs where user is the trainee
     */
    public function trainingLogs()
    {
        return $this->hasMany(TrainingLog::class, 'trainee_id');
    }


    /**
     * Get examiner profile if user is an examiner
     */
    public function examiner()
    {
        return $this->hasOne(Examiner::class);
    }

    /**
     * Check if user is an examiner
     */
    public function isExaminer(): bool
    {
        return $this->examiner()->exists();
    }

    /**
     * Get CPTs where user is the trainee
     */
    public function cpts()
    {
        return $this->hasMany(Cpt::class, 'trainee_id');
    }

    /**
     * Get CPTs where user is the examiner
     */
    public function examinedCpts()
    {
        return $this->hasMany(Cpt::class, 'examiner_id');
    }

    /**
     * Get CPTs where user is the local contact
     */
    public function localCpts()
    {
        return $this->hasMany(Cpt::class, 'local_id');
    }
}