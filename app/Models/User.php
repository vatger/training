<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;


class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

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
        'last_known_rating',
        'rating_upgraded_at',
        'solo_days_used',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_rating_change' => 'datetime',
        'is_staff' => 'boolean',
        'is_superuser' => 'boolean',
        'is_admin' => 'boolean',
        'rating' => 'integer',
        'vatsim_id' => 'integer',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'solo_days_used' => 'integer',
    ];

    private $permissionCache = null;
    private $cotCourseIdsCache = null;
    private $lmFirsCache = null;

    public function getRouteKeyName()
    {
        return 'vatsim_id';
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function isMentor(): bool
    {
        return $this->hasAnyRole(['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor', 'ATD Leitung', 'VATGER Leitung']);
    }

    public function isSuperuser(): bool
    {
        return $this->is_superuser === true || $this->is_admin === true;
    }

    public function isLeadership(): bool
    {
        return $this->hasAnyRole(['ATD Leitung', 'VATGER Leitung']);
    }

    public function hasAnyRole(array $roles): bool
    {
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }

        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function scopeMentors($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor']);
        });
    }

    public function scopeLeadership($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['ATD Leitung', 'VATGER Leitung']);
        });
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function isVatsimUser(): bool
    {
        return !empty($this->vatsim_id);
    }

    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeVatsimUsers($query)
    {
        return $query->whereNotNull('vatsim_id');
    }

    public function endorsementActivities()
    {
        return $this->hasMany(EndorsementActivity::class, 'vatsim_id', 'vatsim_id');
    }

    public function hasActiveTier1Endorsements(): bool
    {
        if (!$this->isVatsimUser()) {
            return false;
        }

        return $this->endorsementActivities()
            ->where('activity_minutes', '>=', config('services.vateud.min_activity_minutes', 180))
            ->exists();
    }

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

        return [
            'tier1_count' => $tier1Count,
            'tier2_count' => 0,
            'solo_count' => 0,
            'low_activity_count' => $lowActivityCount,
        ];
    }

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

    public function activeCourses()
    {
        return $this->belongsToMany(Course::class, 'course_trainees')
            ->whereNull('course_trainees.completed_at')
            ->withPivot([
                'claimed_by_mentor_id',
                'claimed_at',
                'completed_at',
                'remarks',
                'remark_author_id',
                'remark_updated_at',
                'custom_order',
            ])
            ->withTimestamps();
    }

    public function mentorCourses()
    {
        return $this->belongsToMany(Course::class, 'course_mentors');
    }

    public function activeRatingCourses()
    {
        return $this->activeCourses()->where('type', 'RTG');
    }

    public function waitingListEntries()
    {
        return $this->hasMany(WaitingListEntry::class);
    }

    public function familiarisations()
    {
        return $this->hasMany(Familiarisation::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_superuser || $this->is_admin) {
            return true;
        }

        if ($this->isLeadingMentor()) {
            return true;
        }

        return $this->hasPermission('admin.access');
    }

    public function canAccessAdminResource(string $resource): bool
    {
        if ($this->is_superuser || $this->is_admin) {
            return true;
        }

        if ($resource === 'courses' && $this->isLeadingMentor()) {
            return true;
        }

        $permissionName = "admin.{$resource}.view";
        return $this->hasPermission($permissionName);
    }

    public function canEditAdminResource(string $resource): bool
    {
        if ($this->is_superuser || $this->is_admin) {
            return true;
        }

        if ($resource === 'courses' && $this->isLeadingMentor()) {
            return true;
        }

        $permissionName = "admin.{$resource}.edit";
        return $this->hasPermission($permissionName);
    }

    public function trainingLogs()
    {
        return $this->hasMany(TrainingLog::class, 'trainee_id');
    }

    public function examiner()
    {
        return $this->hasOne(Examiner::class);
    }

    public function isExaminer(): bool
    {
        return $this->examiner()->exists();
    }

    public function cpts()
    {
        return $this->hasMany(Cpt::class, 'trainee_id');
    }

    public function examinedCpts()
    {
        return $this->hasMany(Cpt::class, 'examiner_id');
    }

    public function localCpts()
    {
        return $this->hasMany(Cpt::class, 'local_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    public function chiefOfTrainingCourses()
    {
        return $this->belongsToMany(Course::class, 'chief_of_trainings');
    }

    public function leadingMentorFirs()
    {
        return $this->hasMany(LeadingMentor::class);
    }

    private function loadPermissionsOnce(): void
    {
        if ($this->permissionCache !== null) {
            return;
        }

        if (!$this->relationLoaded('permissions')) {
            $this->load('permissions');
        }
        if (!$this->relationLoaded('roles')) {
            $this->load('roles.permissions');
        }

        $this->permissionCache = $this->permissions->pluck('name')->merge(
            $this->roles->flatMap->permissions->pluck('name')
        )->unique()->values()->all();
    }

    public function hasPermission(string $permissionName): bool
    {
        $this->loadPermissionsOnce();
        return in_array($permissionName, $this->permissionCache);
    }

    public function getChiefOfTrainingCourseIds(): array
    {
        if ($this->cotCourseIdsCache === null) {
            $this->cotCourseIdsCache = \DB::table('chief_of_trainings')
                ->where('user_id', $this->id)
                ->pluck('course_id')
                ->all();
        }
        return $this->cotCourseIdsCache;
    }

    public function getLeadingMentorFirs(): array
    {
        if ($this->lmFirsCache === null) {
            if (!$this->relationLoaded('leadingMentorFirs')) {
                $this->load('leadingMentorFirs');
            }
            $this->lmFirsCache = $this->leadingMentorFirs->pluck('fir')->all();
        }
        return $this->lmFirsCache;
    }

    public function isChiefOfTrainingForCourse(int $courseId): bool
    {
        return in_array($courseId, $this->getChiefOfTrainingCourseIds());
    }

    public function isLeadingMentorForFir(string $fir): bool
    {
        return in_array($fir, $this->getLeadingMentorFirs());
    }

    private function findCourseByPosition(string $position): ?Course
    {
        $parts = explode('_', $position);
        if (count($parts) < 2) {
            return null;
        }

        $airportIcao = $parts[0];

        if (count($parts) > 2 && $parts[1] === 'GNDDEL') {
            $positionType = 'GND';
        } else {
            $positionType = $parts[1];
        }

        return Course::where('airport_icao', $airportIcao)
            ->where('position', $positionType)
            ->first();
    }
    
    public function canRemoveEndorsementForPosition(string $position): bool
    {
        if ($this->is_superuser || $this->is_admin) {
            return true;
        }

        $allRelatedCourses = $this->getAllCoursesForPosition($position);

        if ($allRelatedCourses->isEmpty()) {

            $lmFirs = $this->getLeadingMentorFirs();
            if (!empty($lmFirs)) {
                $result = $this->endorsementMatchesLeadingMentorFir($position, $lmFirs);
                return $result;
            }
            return false;
        }

        // Check CoT/LM for each course
        foreach ($allRelatedCourses as $course) {
            $isCoT = $this->isChiefOfTrainingForCourse($course->id);
            $canManage = $this->canManageEndorsementsFor($course);

            if ($isCoT) {
                return true;
            }

            if ($canManage) {
                return true;
            }
        }

        $hasAnyCoT = \DB::table('chief_of_trainings')
            ->whereIn('course_id', $allRelatedCourses->pluck('id'))
            ->exists();

        if (!$hasAnyCoT) {
            $isMentorForAnyCourse = $allRelatedCourses->contains(function ($course) {
                $isMentor = $this->mentorCourses()->where('courses.id', $course->id)->exists();

                return $isMentor;
            });

            if ($isMentorForAnyCourse) {
                return true;
            }
        }
        $lmFirs = $this->getLeadingMentorFirs();
        if (!empty($lmFirs)) {
            $result = $this->endorsementMatchesLeadingMentorFir($position, $lmFirs);
            return $result;
        }

        return false;
    }

    private function endorsementMatchesLeadingMentorFir(string $position, array $lmFirs): bool
    {
        $positionUpper = strtoupper($position);

        foreach ($lmFirs as $fir) {
            $firUpper = strtoupper($fir);

            if (str_contains($positionUpper, $firUpper)) {
                return true;
            }

            $firNameMap = [
                'EDWW' => ['BREMEN', 'EDWW'],
                'EDGG' => ['LANGEN', 'EDGG'],
                'EDMM' => ['MÜNCHEN', 'MUNICH', 'EDMM'],
            ];

            if (isset($firNameMap[$firUpper])) {
                foreach ($firNameMap[$firUpper] as $keyword) {
                    if (str_contains($positionUpper, $keyword)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    public function getFirFromMentorGroup(?string $groupName): ?string
    {
        if (!$groupName) {
            return null;
        }

        if (str_contains($groupName, 'EDGG'))
            return 'EDGG';
        if (str_contains($groupName, 'EDMM'))
            return 'EDMM';
        if (str_contains($groupName, 'EDWW'))
            return 'EDWW';

        return null;
    }

    public function getAccessibleCourseIds(): array
    {
        if ($this->is_superuser || $this->is_admin) {
            return Course::pluck('id')->toArray();
        }

        $courseIds = [];

        $mentorCourseIds = \DB::table('course_mentors')
            ->where('user_id', $this->id)
            ->pluck('course_id')
            ->toArray();
        $courseIds = array_merge($courseIds, $mentorCourseIds);

        $cotCourseIds = $this->getChiefOfTrainingCourseIds();
        $courseIds = array_merge($courseIds, $cotCourseIds);

        $lmFirs = $this->getLeadingMentorFirs();

        if (!empty($lmFirs)) {
            $lmCourseIds = \DB::table('courses')
                ->join('roles', 'courses.mentor_group_id', '=', 'roles.id')
                ->where(function ($query) use ($lmFirs) {
                    foreach ($lmFirs as $fir) {
                        $query->orWhere('roles.name', 'LIKE', "%{$fir}%");
                    }
                })
                ->pluck('courses.id')
                ->toArray();
            $courseIds = array_merge($courseIds, $lmCourseIds);
        }

        return array_unique($courseIds);
    }

    public function canViewCourse(Course $course): bool
    {
        if ($this->is_superuser || $this->is_admin) {
            return true;
        }

        if ($this->isChiefOfTrainingForCourse($course->id)) {
            return true;
        }

        if ($this->mentorCourses()->where('courses.id', $course->id)->exists()) {
            return true;
        }

        if ($course->mentor_group_id) {
            $mentorGroupName = $course->mentorGroup?->name;
            if ($mentorGroupName) {
                $fir = $this->getFirFromMentorGroup($mentorGroupName);
                if ($fir && $this->isLeadingMentorForFir($fir)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canEditTrainingLog(TrainingLog $log): bool
    {
        if ($this->is_superuser || $this->is_admin) {
            return true;
        }

        if ($this->id === $log->mentor_id) {
            return true;
        }

        if (!$log->course_id) {
            return false;
        }

        if ($this->isChiefOfTrainingForCourse($log->course_id)) {
            return true;
        }

        $course = $log->course;
        if (!$course || !$course->mentor_group_id) {
            return false;
        }

        $mentorGroupName = $course->mentorGroup?->name;
        if (!$mentorGroupName) {
            return false;
        }

        $fir = $this->getFirFromMentorGroup($mentorGroupName);
        if (!$fir) {
            return false;
        }

        return $this->isLeadingMentorForFir($fir);
    }

    public function canManageEndorsementsFor(Course $course): bool
    {
        if ($this->is_superuser || $this->is_admin) {
            return true;
        }

        if ($this->isChiefOfTrainingForCourse($course->id)) {
            return true;
        }

        if (!$course->mentor_group_id) {
            return false;
        }

        $mentorGroupName = $course->mentorGroup?->name;
        if (!$mentorGroupName) {
            return false;
        }

        $fir = $this->getFirFromMentorGroup($mentorGroupName);
        if (!$fir) {
            return false;
        }

        return $this->isLeadingMentorForFir($fir);
    }

    protected function getAllCoursesForPosition(string $position): \Illuminate\Support\Collection
    {
        $parts = explode('_', $position);
        if (count($parts) < 2) {
            return collect();
        }

        $airportIcao = $parts[0];

        if ($parts[1] === 'GNDDEL' || (count($parts) > 2 && in_array('GNDDEL', $parts))) {
            $positionType = 'GND';
        } else {
            $positionType = $parts[1];
        }

        return Course::where('airport_icao', $airportIcao)
            ->where('position', $positionType)
            ->get();
    }

    public function isChiefOfTraining(): bool
    {
        return !empty($this->getChiefOfTrainingCourseIds());
    }

    public function isLeadingMentor(): bool
    {
        return !empty($this->getLeadingMentorFirs());
    }

    public function waitingListRestrictions()
    {
        return $this->hasMany(WaitingListRestriction::class);
    }

    public function isRestrictedFrom(string $type): bool
    {
        return $this->waitingListRestrictions()
            ->where('type', $type)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->exists();
    }
}