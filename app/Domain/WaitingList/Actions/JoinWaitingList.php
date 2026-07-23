<?php

namespace App\Domain\WaitingList\Actions;

use App\Domain\WaitingList\Events\WaitingListJoined;
use App\Models\Course;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\WaitingListEntry;
use App\Services\CourseValidationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JoinWaitingList
{
    public function __construct(
        private CourseValidationService $validationService,
    ) {}

    public function execute(Course $course, User $user): array
    {
        [$canJoin, $reason] = $this->validationService->canUserJoinCourse($course, $user);

        if (! $canJoin) {
            return [false, $reason];
        }

        if (WaitingListEntry::where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
            return [false, 'You are already on the waiting list for this course.'];
        }

        if ($course->type === 'RTG' && WaitingListEntry::whereHas('course', fn ($q) => $q->where('type', 'RTG'))->where('user_id', $user->id)->exists()) {
            return [false, 'You are already on the waiting list for a rating course. You can only join one rating course at a time.'];
        }

        if (in_array($course->type, ['EDMT', 'FAM']) && WaitingListEntry::whereHas('course', fn ($q) => $q->whereIn('type', ['EDMT', 'FAM']))->where('user_id', $user->id)->exists()) {
            return [false, 'You are already on the waiting list for an endorsement or familiarisation course. You can only join one at a time.'];
        }

        try {
            $entry = null;

            DB::transaction(function () use ($course, $user, &$entry) {
                $settings = UserSetting::where('user_id', $user->id)->first();

                $entry = WaitingListEntry::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'date_added' => now(),
                    'activity' => 0,
                    'hours_updated' => Carbon::createFromDate(2000, 1, 1),
                    'remarks' => ($settings?->english_only) ? 'EN' : '',
                ]);
            });

            event(new WaitingListJoined($entry, $course, $user));

            return [true, 'Successfully joined waiting list.'];
        } catch (\Exception $e) {
            Log::error('Failed to join waiting list', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);

            return [false, 'Failed to join waiting list. Please try again.'];
        }
    }
}
