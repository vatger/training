<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\Familiarisation;
use App\Services\VatEudService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CourseValidationService
{
    protected VatEudService $vatEudService;
    protected VatsimActivityService $activityService;

    public function __construct(VatEudService $vatEudService, VatsimActivityService $activityService)
    {
        $this->vatEudService = $vatEudService;
        $this->activityService = $activityService;
    }

    public function canUserJoinCourse(Course $course, User $user): array
    {
        try {
            $roster = $this->getRoster();
            $isOnRoster = in_array($user->vatsim_id, $roster);
        } catch (\Exception $e) {
            Log::warning('Failed to fetch roster, allowing course join', ['error' => $e->getMessage()]);
            $isOnRoster = false;
        }

        $isGerSubdivision = $user->subdivision === 'GER';

        if ($isGerSubdivision && $isOnRoster) {
            if ($course->type === 'RST') {
                return [false, 'You are already on the roster and cannot join roster reentry courses.'];
            }
            if ($course->type === 'GST') {
                return [false, 'You are not allowed to join visitor courses.'];
            }
        } elseif ($isGerSubdivision && !$isOnRoster) {
            if ($course->type !== 'RST') {
                return [false, 'You must complete roster reentry before joining other courses.'];
            }
        } else {
            if ($course->type !== 'GST') {
                return [false, 'As a visitor, you can only join visitor courses (GST).'];
            }
        }

        if (
            $course->type !== 'GST' &&
            !($course->min_rating <= $user->rating && $user->rating <= $course->max_rating)
        ) {
            return [false, 'You do not have the required rating for this course.'];
        }

        if ($course->type === 'RTG') {
            $hasActiveRtg = Cache::remember(
                "user_{$user->id}_has_active_rtg",
                now()->addMinutes(5),
                fn() => $user->activeRatingCourses()
                    ->wherePivot('completed_at', null)
                    ->exists()
            );

            if ($hasActiveRtg) {
                return [false, 'You already have an active RTG course.'];
            }
        }

        if (
            $course->familiarisation_sector_id &&
            Familiarisation::where('user_id', $user->id)
                ->where('familiarisation_sector_id', $course->familiarisation_sector_id)
                ->exists()
        ) {
            return [false, 'You already have a familiarisation for this course.'];
        }

        $endorsementGroups = $course->endorsementGroups();
        if ($endorsementGroups->isNotEmpty()) {
            $userEndorsements = $this->getUserEndorsements($user->vatsim_id);
            $hasAllEndorsements = $endorsementGroups->every(function ($group) use ($userEndorsements) {
                return $userEndorsements->contains($group);
            });

            if ($hasAllEndorsements && $course->type === 'EDMT') {
                return [false, 'You already have the required endorsements for this course.'];
            }
        }

        if (
            $user->rating === 3 &&
            $course->type === 'RTG' &&
            $course->position === 'APP'
        ) {
            $minDays = (int) config('services.training.s3_rating_change_days', 90);

            if ($user->last_rating_change) {
                $daysSinceRatingChange = Carbon::parse($user->last_rating_change)->diffInDays(now());
                if ($daysSinceRatingChange < $minDays) {
                    return [false, 'Your last rating change was less than 3 months ago. You cannot join an S3 course yet.'];
                }
            }
        }

        return [true, ''];
    }

    public function getRoster(): array
    {
        return Cache::remember('vateud:roster', now()->addHours(1), function () {
            try {
                $response = Http::withHeaders([
                    'X-API-KEY' => config('services.vateud.token'),
                    'Accept' => 'application/json',
                    'User-Agent' => 'VATGER Training System',
                ])
                    ->timeout(5)
                    ->get('https://core.vateud.net/api/facility/roster');

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data']['controllers'] ?? [];
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch roster from VatEUD', ['error' => $e->getMessage()]);
            }

            return [];
        });
    }

    public function getUserEndorsements(int $vatsimId): \Illuminate\Support\Collection
    {
        return Cache::remember("user_endorsements:{$vatsimId}", now()->addHours(2), function () use ($vatsimId) {
            try {
                $tier1 = $this->vatEudService->getTier1Endorsements();
                return collect($tier1)
                    ->where('user_cid', $vatsimId)
                    ->pluck('position');
            } catch (\Exception $e) {
                Log::error('Failed to fetch user endorsements', [
                    'vatsim_id' => $vatsimId,
                    'error' => $e->getMessage()
                ]);
                return collect();
            }
        });
    }

    public function hasMinimumActivity(Course $course, User $user): bool
    {
        if ($course->type !== 'RTG' || in_array($course->position, ['GND', 'TWR'])) {
            return true;
        }

        $minHours = config('services.training.min_hours', 25);
        $activityHours = $this->getActivityHours($course, $user);

        return $activityHours >= $minHours;
    }

    public function getActivityHours(Course $course, User $user): float
    {
        try {
            return 0.0;
        } catch (\Exception $e) {
            Log::error('Failed to get activity hours', [
                'course_id' => $course->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return 0.0;
        }
    }

    public function isUserOnRoster(int $vatsimId): bool
    {
        try {
            $roster = $this->getRoster();
            return in_array($vatsimId, $roster);
        } catch (\Exception $e) {
            Log::warning('Failed to check roster status', [
                'vatsim_id' => $vatsimId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}