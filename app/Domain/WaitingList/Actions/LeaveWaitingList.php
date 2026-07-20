<?php

namespace App\Domain\WaitingList\Actions;

use App\Domain\WaitingList\Events\WaitingListLeft;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Support\Facades\Log;

class LeaveWaitingList
{
    public function execute(Course $course, User $user): array
    {
        try {
            $entry = WaitingListEntry::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (! $entry) {
                return [false, 'You are not on the waiting list for this course.'];
            }

            event(new WaitingListLeft($entry, $course, $user));

            $entry->delete();

            return [true, 'Successfully left waiting list.'];
        } catch (\Exception $e) {
            Log::error('Failed to leave waiting list', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);

            return [false, 'Failed to leave waiting list. Please try again.'];
        }
    }
}
