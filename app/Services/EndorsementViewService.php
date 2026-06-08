<?php

namespace App\Services;

use App\Integrations\VatEud\VatEudService;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Models\Course;
use App\Models\EndorsementActivity;
use App\Models\Tier2Endorsement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EndorsementViewService
{
    public function __construct(
        private VatEudService $vatEudService,
        private MoodleClientInterface $moodle,
    ) {}

    public function getTraineeData(User $user): array
    {
        return [
            'tier1Endorsements' => $this->getUserTier1Endorsements($user->vatsim_id),
            'tier2Endorsements' => $this->getUserTier2Endorsements($user->vatsim_id),
            'soloEndorsements' => $this->getUserSoloEndorsements($user->vatsim_id),
            'isVatsimUser' => true,
        ];
    }

    public function getMentorData(User $user): array
    {
        $allTier1 = $this->vatEudService->getTier1Endorsements();

        $endorsementIds = collect($allTier1)->pluck('id')->toArray();
        $vatsimIds = collect($allTier1)->pluck('user_cid')->unique()->toArray();

        $activities = EndorsementActivity::whereIn('endorsement_id', $endorsementIds)
            ->get()
            ->keyBy('endorsement_id');

        $users = User::whereIn('vatsim_id', $vatsimIds)
            ->get()
            ->keyBy('vatsim_id');

        $lmFirs = ($user->is_superuser || $user->is_admin)
            ? collect()
            : $user->leadingMentorFirs()->pluck('fir');

        $allowedPositions = $this->resolveAllowedPositions($user, $lmFirs);

        $endorsements = collect($allTier1)
            ->map(fn($e) => $this->mapEndorsement($e, $activities, $users))
            ->filter()
            ->filter(fn($e) => $this->isVisibleToUser($e, $user, $allowedPositions, $lmFirs))
            ->values();

        $endorsementsByPosition = $endorsements
            ->groupBy('position')
            ->map(fn($items, $position) => [
                'position' => $position,
                'position_name' => $this->getPositionFullName($position),
                'airport_icao' => explode('_', $position)[0],
                'position_type' => $this->getPositionType($position),
                'endorsements' => $items->values(),
            ])
            ->values();

        $canRemovePositions = ($user->is_superuser || $user->is_admin)
            ? null
            : $this->resolveRemovablePositions($endorsements, $user, $lmFirs);

        return [
            'endorsementGroups' => $endorsementsByPosition,
            'userPermissions' => [
                'canRemoveForPositions' => $canRemovePositions,
                'canRemoveAny' => ($user->is_superuser || $user->is_admin)
                    || (! empty($canRemovePositions) && count($canRemovePositions) > 0),
                'isAdmin' => $user->is_superuser || $user->is_admin,
            ],
        ];
    }

    private function mapEndorsement(array $endorsement, $activities, $users): ?array
    {
        $activity = $activities->get($endorsement['id']);
        if (! $activity) {
            return null;
        }

        $createdAt = Carbon::parse($endorsement['created_at']);
        $olderThanSixMonths = $createdAt->lte(now()->subMonths(6));
        $hasGoodActivity = $activity->activity_hours >= 3;

        if (! $hasGoodActivity && ! $olderThanSixMonths) {
            return null;
        }

        $user = $users->get($endorsement['user_cid']);

        return [
            'id' => $activity->id,
            'endorsementId' => $endorsement['id'],
            'position' => $endorsement['position'],
            'vatsimId' => $endorsement['user_cid'],
            'userName' => $user?->name ?? 'Unknown',
            'activity' => $activity->activity_minutes,
            'activityHours' => $activity->activity_hours,
            'status' => $activity->status,
            'progress' => $activity->progress,
            'eligibleSince' => $activity->eligible_since,
            'removalDate' => $activity->removal_date?->format('Y-m-d'),
            'removalDays' => $activity->removal_date
                ? $activity->removal_date->diffInDays(now(), false)
                : -1,
            'eligibleForRemoval' => $olderThanSixMonths,
            'endorsedAt' => $createdAt->format('Y-m-d'),
        ];
    }

    private function isVisibleToUser(array $endorsement, User $user, $allowedPositions, $lmFirs): bool
    {
        if ($user->is_superuser || $user->is_admin) {
            return true;
        }

        if ($allowedPositions && $allowedPositions->contains($endorsement['position'])) {
            return true;
        }

        if ($lmFirs->isNotEmpty()) {
            return $this->positionMatchesLmFir($endorsement['position'], $lmFirs);
        }

        return false;
    }

    private function resolveAllowedPositions(User $user, $lmFirs)
    {
        if ($user->is_superuser || $user->is_admin) {
            return null;
        }

        $mentorPositions = $this->coursePositionStrings($user->mentorCourses);
        $cotPositions = $this->coursePositionStrings($user->chiefOfTrainingCourses);

        $lmPositions = collect();
        foreach ($lmFirs as $fir) {
            $firCourses = Course::whereHas('mentorGroup', fn($q) => $q->where('name', 'LIKE', "%{$fir}%"))->get();
            $lmPositions = $lmPositions->merge($this->coursePositionStrings($firCourses));
        }

        return $mentorPositions->merge($cotPositions)->merge($lmPositions)->unique()->values();
    }

    private function resolveRemovablePositions($endorsements, User $user, $lmFirs): array
    {
        return $endorsements
            ->pluck('position')
            ->unique()
            ->filter(function (string $position) use ($user, $lmFirs) {
                $courses = $this->getAllCoursesForPosition($position);

                if ($courses->isEmpty()) {
                    return $lmFirs->isNotEmpty() && $this->positionMatchesLmFir($position, $lmFirs);
                }

                foreach ($courses as $course) {
                    if ($user->canManageEndorsementsFor($course)) {
                        return true;
                    }
                }

                $hasAnyCoT = DB::table('chief_of_trainings')
                    ->whereIn('course_id', $courses->pluck('id'))
                    ->exists();

                if (! $hasAnyCoT) {
                    $isMentor = $courses->contains(
                        fn($course) => $user->mentorCourses()->where('courses.id', $course->id)->exists()
                    );

                    if ($isMentor) {
                        return true;
                    }
                }

                return $lmFirs->isNotEmpty() && $this->positionMatchesLmFir($position, $lmFirs);
            })
            ->values()
            ->toArray();
    }

    private function coursePositionStrings($courses): \Illuminate\Support\Collection
    {
        return $courses->flatMap(function (Course $course) {
            $airport = $course->airport_icao;
            $position = $course->position;

            return $position === 'GND'
                ? ["{$airport}_GNDDEL"]
                : ["{$airport}_{$position}"];
        })->unique()->values();
    }

    public function positionMatchesLmFir(string $position, $lmFirs): bool
    {
        $positionUpper = strtoupper($position);

        $firNameMap = [
            'EDWW' => ['BREMEN', 'EDWW'],
            'EDGG' => ['LANGEN', 'EDGG'],
            'EDMM' => ['MÜNCHEN', 'MUNICH', 'EDMM'],
        ];

        foreach ($lmFirs as $fir) {
            $firUpper = strtoupper($fir);

            if (str_contains($positionUpper, $firUpper)) {
                return true;
            }

            foreach ($firNameMap[$firUpper] ?? [] as $keyword) {
                if (str_contains($positionUpper, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getAllCoursesForPosition(string $position): \Illuminate\Support\Collection
    {
        $parts = explode('_', $position);
        if (count($parts) < 2) {
            return collect();
        }

        $airportIcao = $parts[0];
        $positionType = ($parts[1] === 'GNDDEL' || in_array('GNDDEL', $parts)) ? 'GND' : $parts[1];

        return Course::where('airport_icao', $airportIcao)
            ->where('position', $positionType)
            ->get();
    }

    private function getUserTier1Endorsements(int $vatsimId): array
    {
        $tier1Endorsements = collect($this->vatEudService->getTier1Endorsements())
            ->where('user_cid', $vatsimId);

        if ($tier1Endorsements->isEmpty()) {
            return [];
        }

        $activities = EndorsementActivity::whereIn('endorsement_id', $tier1Endorsements->pluck('id')->toArray())
            ->get()
            ->keyBy('endorsement_id');

        return $tier1Endorsements
            ->map(function ($endorsement) use ($activities) {
                $activity = $activities->get($endorsement['id']);
                if (! $activity) {
                    return null;
                }

                return [
                    'position' => $endorsement['position'],
                    'fullName' => $this->getPositionFullName($endorsement['position']),
                    'type' => $this->getPositionType($endorsement['position']),
                    'activity' => $activity->activity_minutes,
                    'activityHours' => $activity->activity_hours,
                    'status' => $activity->status,
                    'progress' => $activity->progress,
                    'lastActivity' => $activity->last_activity_date?->format('Y-m-d') ?? 'Never',
                    'removalDate' => $activity->removal_date?->format('Y-m-d'),
                    'lastUpdated' => $activity->last_updated?->format('Y-m-d H:i'),
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function getUserTier2Endorsements(int $vatsimId): array
    {
        $activePositions = collect($this->vatEudService->getTier2Endorsements())
            ->where('user_cid', $vatsimId)
            ->pluck('position')
            ->toArray();

        return Tier2Endorsement::all()
            ->map(function (Tier2Endorsement $endorsement) use ($vatsimId, $activePositions) {
                $hasEndorsement = in_array($endorsement->position, $activePositions);
                $moodleCompleted = false;

                if (! $hasEndorsement && $endorsement->moodle_course_id) {
                    $moodleCompleted = $this->moodle->getCourseCompletion(
                        $vatsimId,
                        $endorsement->moodle_course_id
                    );
                }

                return [
                    'id' => $endorsement->id,
                    'position' => $endorsement->position,
                    'name' => $endorsement->name,
                    'fullName' => $endorsement->name,
                    'type' => $this->getPositionType($endorsement->position),
                    'status' => $hasEndorsement ? 'active' : ($moodleCompleted ? 'completed' : 'available'),
                    'moodleCourseId' => $endorsement->moodle_course_id,
                    'hasEndorsement' => $hasEndorsement,
                    'moodleCompleted' => $moodleCompleted,
                ];
            })
            ->toArray();
    }

    private function getUserSoloEndorsements(int $vatsimId): array
    {
        return collect($this->vatEudService->getSoloEndorsements())
            ->where('user_cid', $vatsimId)
            ->map(function ($solo) {
                $expiresAt = null;
                if (! empty($solo['expiry'])) {
                    try {
                        $expiresAt = Carbon::parse($solo['expiry'])->toDateString();
                    } catch (\Exception) {
                    }
                }

                return [
                    'position' => $solo['position'],
                    'fullName' => $this->getPositionFullName($solo['position']),
                    'type' => $this->getPositionType($solo['position']),
                    'status' => 'active',
                    'mentor' => $this->getMentorName($solo['instructor_cid'] ?? null),
                    'expiresAt' => $expiresAt,
                ];
            })
            ->values()
            ->toArray();
    }

    private function getPositionFullName(string $position): string
    {
        return match ($position) {
            'EDDF_TWR' => 'Frankfurt Tower',
            'EDDF_APP' => 'Frankfurt Approach',
            'EDDF_GNDDEL' => 'Frankfurt Ground/Delivery',
            'EDDL_TWR' => 'Düsseldorf Tower',
            'EDDL_APP' => 'Düsseldorf Approach',
            'EDDL_GNDDEL' => 'Düsseldorf Ground/Delivery',
            'EDDK_TWR' => 'Köln Tower',
            'EDDK_APP' => 'Köln Approach',
            'EDDS_TWR' => 'Stuttgart Tower',
            'EDDH_TWR' => 'Hamburg Tower',
            'EDDH_APP' => 'Hamburg Approach',
            'EDDH_GNDDEL' => 'Hamburg Ground/Delivery',
            'EDDM_TWR' => 'München Tower',
            'EDDM_APP' => 'München Approach',
            'EDDM_GNDDEL' => 'München Ground/Delivery',
            'EDDB_APP' => 'Berlin Approach',
            'EDDB_TWR' => 'Berlin Tower',
            'EDDB_GNDDEL' => 'Berlin Ground/Delivery',
            'EDWW_CTR' => 'Bremen Big',
            'EDWW_W_CTR' => 'Bremen West',
            'EDGG_CSH_CTR' => 'Central South (High)',
            'EDXX_AFIS' => 'AFIS Tower',
            default => $position,
        };
    }

    private function getPositionType(string $position): string
    {
        return match (true) {
            str_ends_with($position, '_CTR') => 'CTR',
            str_ends_with($position, '_APP') => 'APP',
            str_ends_with($position, '_TWR') => 'TWR',
            str_ends_with($position, '_GNDDEL') => 'GNDDEL',
            default => 'TWR',
        };
    }

    private function getMentorName(?int $vatsimId): string
    {
        if (! $vatsimId) {
            return 'Unknown';
        }

        $user = User::where('vatsim_id', $vatsimId)->first();

        return $user ? $user->name : "ID: {$vatsimId}";
    }
}