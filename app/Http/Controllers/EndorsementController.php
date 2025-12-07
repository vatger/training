<?php

namespace App\Http\Controllers;

use App\Models\EndorsementActivity;
use App\Models\Tier2Endorsement;
use App\Models\User;
use App\Models\Course;
use App\Services\VatEudService;
use App\Services\VatsimActivityService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class EndorsementController extends Controller
{
    protected VatEudService $vatEudService;
    protected VatsimActivityService $activityService;

    public function __construct(VatEudService $vatEudService, VatsimActivityService $activityService)
    {
        $this->vatEudService = $vatEudService;
        $this->activityService = $activityService;
    }

    public function traineeView(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->isVatsimUser()) {
            return Inertia::render('endorsements/trainee', [
                'tier1Endorsements' => [],
                'tier2Endorsements' => [],
                'soloEndorsements' => [],
                'isVatsimUser' => false,
            ]);
        }

        try {
            $tier1Data = $this->getUserTier1Endorsements($user->vatsim_id);
            $tier2Data = $this->getUserTier2Endorsements($user->vatsim_id);
            $soloData = $this->getUserSoloEndorsements($user->vatsim_id);

            return Inertia::render('endorsements/trainee', [
                'tier1Endorsements' => $tier1Data,
                'tier2Endorsements' => $tier2Data,
                'soloEndorsements' => $soloData,
                'isVatsimUser' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading endorsements', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('endorsements/trainee', [
                'tier1Endorsements' => [],
                'tier2Endorsements' => [],
                'soloEndorsements' => [],
                'isVatsimUser' => true,
                'error' => 'Failed to load endorsement data',
            ]);
        }
    }

    public function mentorView(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->isMentor() && !$user->is_superuser) {
            abort(403, 'Access denied. Mentor privileges required.');
        }

        if ($user->is_superuser || $user->is_admin) {
            $courses = Course::all();
        } else {
            $courses = $user->mentorCourses;
        }

        $allowedPositions = $courses->map(function ($course) {
            return [
                'airport' => $course->airport_icao,
                'position' => $course->position,
            ];
        })->unique(function ($item) {
            return $item['airport'] . '_' . $item['position'];
        });

        $allTier1 = $this->vatEudService->getTier1Endorsements();

        $endorsementIds = collect($allTier1)->pluck('id')->toArray();
        $vatsimIds = collect($allTier1)->pluck('user_cid')->unique()->toArray();

        $activities = EndorsementActivity::whereIn('endorsement_id', $endorsementIds)
            ->get()
            ->keyBy('endorsement_id');

        $users = User::whereIn('vatsim_id', $vatsimIds)
            ->get()
            ->keyBy('vatsim_id');

        $allEndorsements = collect($allTier1)
            ->map(function ($endorsement) use ($activities, $users) {
                $activity = $activities->get($endorsement['id']);

                if (!$activity) {
                    Log::info('No activity record found for endorsement', [
                        'endorsement_id' => $endorsement['id'],
                        'position' => $endorsement['position']
                    ]);
                    return null;
                }

                $user = $users->get($endorsement['user_cid']);

                return [
                    'id' => $activity->id,
                    'endorsementId' => $endorsement['id'],
                    'position' => $endorsement['position'],
                    'vatsimId' => $endorsement['user_cid'],
                    'userName' => $user ? $user->name : 'Unknown',
                    'activity' => $activity->activity_minutes,
                    'activityHours' => $activity->activity_hours,
                    'status' => $activity->status,
                    'progress' => $activity->progress,
                    'removalDate' => $activity->removal_date?->format('Y-m-d'),
                    'removalDays' => $activity->removal_date ? $activity->removal_date->diffInDays(now(), false) : -1,
                ];
            })
            ->filter()
            ->filter(function ($endorsement) use ($allowedPositions, $user) {
                if ($user->is_superuser || $user->is_admin) {
                    return true;
                }

                $parts = explode('_', $endorsement['position']);
                $airport = $parts[0];
                $positionType = end($parts);

                if ($positionType === 'GNDDEL') {
                    $positionType = 'GND';
                }

                return $allowedPositions->contains(function ($allowed) use ($airport, $positionType) {
                    return $allowed['airport'] === $airport && $allowed['position'] === $positionType;
                });
            })
            ->values()
            ->toArray();

        $endorsementsByPosition = collect($allEndorsements)
            ->groupBy('position')
            ->map(function ($endorsements, $position) {
                return [
                    'position' => $position,
                    'position_name' => $this->getPositionFullName($position),
                    'airport_icao' => explode('_', $position)[0],
                    'position_type' => $this->getPositionType($position),
                    'endorsements' => $endorsements->toArray(),
                ];
            })
            ->values();

        return Inertia::render('endorsements/manage', [
            'endorsementGroups' => $endorsementsByPosition,
        ]);
    }

    public function removeTier1(Request $request, int $endorsementId)
    {
        $user = $request->user();
        
        if (!$user->isMentor() && !$user->is_superuser) {
            return back()->with('error', 'Access denied');
        }

        try {
            $endorsement = EndorsementActivity::where('endorsement_id', $endorsementId)->first();
            
            if (!$endorsement) {
                return back()->with('error', 'Endorsement not found');
            }

            if (!$user->is_superuser && !$user->is_admin) {
                $hasCourse = $user->mentorCourses()->where(function ($query) use ($endorsement) {
                    $parts = explode('_', $endorsement->position);
                    $airport = $parts[0];
                    $position = end($parts);

                    if ($position === 'GNDDEL') {
                        $position = 'GND';
                    }

                    $query->where('airport_icao', $airport)
                        ->where('position', $position);
                })->exists();

                if (!$hasCourse) {
                    return back()->with('error', 'You do not have permission to manage this endorsement');
                }
            }

            if ($endorsement->removal_date) {
                return back()->with('error', 'Endorsement already marked for removal');
            }

            $minRequiredMinutes = config('services.vateud.min_activity_minutes', 180);
            if ($endorsement->activity_minutes >= $minRequiredMinutes) {
                return back()->with('error', 'Endorsement has sufficient activity and cannot be marked for removal');
            }

            $endorsement->removal_date = Carbon::now()->addDays(
                config('services.vateud.removal_warning_days', 31)
            );
            $endorsement->removal_notified = false;
            $endorsement->last_updated = Carbon::createFromTimestamp(1);
            $endorsement->save();

            $trainee = User::where('vatsim_id', $endorsement->vatsim_id)->first();
            if ($trainee) {
                ActivityLogger::endorsementRemoved(
                    $endorsement->position,
                    $trainee,
                    $user,
                    'Marked for removal due to low activity'
                );
            }

            return back()->with('success', "Successfully marked {$endorsement->position} for removal");

        } catch (\Exception $e) {
            Log::error('Error marking endorsement for removal', [
                'endorsement_id' => $endorsementId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'An error occurred while marking the endorsement for removal');
        }
    }

    public function requestTier2(Request $request, int $tier2Id)
    {
        $user = $request->user();
        
        if (!$user->isVatsimUser()) {
            return response()->json(['error' => 'VATSIM account required'], 403);
        }

        try {
            $tier2Endorsement = Tier2Endorsement::findOrFail($tier2Id);
            
            $existingTier2 = collect($this->vatEudService->getTier2Endorsements())
                ->where('user_cid', $user->vatsim_id)
                ->where('position', $tier2Endorsement->position)
                ->first();

            if ($existingTier2) {
                return response()->json(['error' => 'You already have this endorsement'], 400);
            }
            
            $success = $this->vatEudService->createTier2Endorsement(
                $user->vatsim_id,
                $tier2Endorsement->position,
                config('services.vateud.atd_lead_cid', 1439797)
            );

            if (!$success) {
                return response()->json(['error' => 'Failed to create endorsement'], 500);
            }

            ActivityLogger::endorsementGranted(
                $tier2Endorsement->position,
                $user,
                $user,
                'tier2'
            );

            return response()->json([
                'success' => true,
                'message' => 'Tier 2 endorsement created successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error requesting Tier 2 endorsement', [
                'tier2_id' => $tier2Id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    protected function getUserTier1Endorsements(int $vatsimId): array
    {
        $allTier1 = $this->vatEudService->getTier1Endorsements();
        $tier1Endorsements = collect($allTier1)->where('user_cid', $vatsimId);

        if ($tier1Endorsements->isEmpty()) {
            return [];
        }

        $endorsementIds = $tier1Endorsements->pluck('id')->toArray();

        $activities = EndorsementActivity::whereIn('endorsement_id', $endorsementIds)
            ->get()
            ->keyBy('endorsement_id');

        $result = [];

        foreach ($tier1Endorsements as $endorsement) {
            $activity = $activities->get($endorsement['id']);

            if (!$activity) {
                continue;
            }

            $lastActivityDate = $activity->last_activity_date
                ? $activity->last_activity_date->format('Y-m-d')
                : 'Never';

            $result[] = [
                'position' => $endorsement['position'],
                'fullName' => $this->getPositionFullName($endorsement['position']),
                'type' => $this->getPositionType($endorsement['position']),
                'activity' => $activity->activity_minutes,
                'activityHours' => $activity->activity_hours,
                'status' => $activity->status,
                'progress' => $activity->progress,
                'lastActivity' => $lastActivityDate,
                'removalDate' => $activity->removal_date?->format('Y-m-d'),
                'lastUpdated' => $activity->last_updated?->format('Y-m-d H:i'),
            ];
        }

        return $result;
    }

    protected function getUserTier2Endorsements(int $vatsimId): array
    {
        $tier2Endorsements = collect($this->vatEudService->getTier2Endorsements())
            ->where('user_cid', $vatsimId)
            ->pluck('position')
            ->toArray();

        $availableTier2 = Tier2Endorsement::all();
        $result = [];

        foreach ($availableTier2 as $endorsement) {
            $result[] = [
                'id' => $endorsement->id,
                'position' => $endorsement->position,
                'name' => $endorsement->name,
                'fullName' => $endorsement->name,
                'type' => $this->getPositionType($endorsement->position),
                'status' => in_array($endorsement->position, $tier2Endorsements) ? 'active' : 'available',
                'moodleCourseId' => $endorsement->moodle_course_id,
                'hasEndorsement' => in_array($endorsement->position, $tier2Endorsements),
            ];
        }

        return $result;
    }

    protected function getUserSoloEndorsements(int $vatsimId): array
    {
        $soloEndorsements = collect($this->vatEudService->getSoloEndorsements())
            ->where('user_cid', $vatsimId);

        $result = [];

        foreach ($soloEndorsements as $solo) {
            $result[] = [
                'position' => $solo['position'],
                'fullName' => $this->getPositionFullName($solo['position']),
                'type' => $this->getPositionType($solo['position']),
                'status' => 'active',
                'mentor' => $this->getMentorName($solo['instructor_cid'] ?? null),
                'expiresAt' => isset($solo['expires_at']) ? Carbon::parse($solo['expires_at'])->format('Y-m-d') : null,
            ];
        }

        return $result;
    }

    protected function getPositionFullName(string $position): string
    {
        $positionNames = [
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
            'EDGG_KTG_CTR' => 'Kitzingen',
            'EDXX_AFIS' => 'AFIS Tower',
        ];

        return $positionNames[$position] ?? $position;
    }

    protected function getPositionType(string $position): string
    {
        if (str_ends_with($position, '_CTR')) {
            return 'CTR';
        } elseif (str_ends_with($position, '_APP')) {
            return 'APP';
        } elseif (str_ends_with($position, '_TWR')) {
            return 'TWR';
        } elseif (str_ends_with($position, '_GNDDEL')) {
            return 'GNDDEL';
        }

        return 'TWR';
    }

    protected function getMentorName(?int $vatsimId): string
    {
        if (!$vatsimId) {
            return 'Unknown';
        }

        $user = User::where('vatsim_id', $vatsimId)->first();
        return $user ? $user->name : "ID: {$vatsimId}";
    }
}