<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WaitingListService
{
    protected CourseValidationService $validationService;

    public function __construct(CourseValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Add user to waiting list
     */
    public function joinWaitingList(Course $course, User $user): array
    {
        [$canJoin, $reason] = $this->validationService->canUserJoinCourse($course, $user);

        if (!$canJoin) {
            return [false, $reason];
        }

        // Check if already on waiting list
        if (
            WaitingListEntry::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->exists()
        ) {
            return [false, 'You are already on the waiting list for this course.'];
        }

        // Check for multiple RTG courses
        if (
            $course->type === 'RTG' &&
            WaitingListEntry::whereHas('course', function ($q) {
                $q->where('type', 'RTG');
            })->where('user_id', $user->id)->exists()
        ) {
            return [false, 'You are already on the waiting list for a rating course. You can only join one rating course at a time.'];
        }

        try {
            DB::transaction(function () use ($course, $user, &$createdEntry) {
                $createdEntry = WaitingListEntry::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'date_added' => now(),
                    'activity' => 0,
                    'hours_updated' => Carbon::createFromDate(2000, 1, 1),
                ]);
            });

            if ($createdEntry) {
                ActivityLogger::waitingListJoined($createdEntry, $course, $user);
            }

            return [true, 'Successfully joined waiting list.'];
        } catch (\Exception $e) {
            Log::error('Failed to join waiting list', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);

            return [false, 'Failed to join waiting list. Please try again.'];
        }
    }

    /**
     * Remove user from waiting list
     */
    public function leaveWaitingList(Course $course, User $user): array
    {
        try {
            $entry = WaitingListEntry::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (!$entry) {
                return [false, 'You are not on the waiting list for this course.'];
            }

            ActivityLogger::waitingListLeft($entry, $course, $user);

            $entry->delete();

            return [true, 'Successfully left waiting list.'];
        } catch (\Exception $e) {
            Log::error('Failed to leave waiting list', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);

            return [false, 'Failed to leave waiting list. Please try again.'];
        }
    }

    /**
     * Start training for a user (move from waiting list to active training)
     */
    public function startTraining(WaitingListEntry $entry, User $mentor): array
    {
        $minActivity = config('services.training.display_activity', 8);

        if ($entry->course->type === 'RTG' && $entry->activity < $minActivity) {
            return [false, 'Trainee does not have sufficient activity to start training.'];
        }

        try {
            DB::transaction(function () use ($entry) {
                $entry->course->activeTrainees()->attach($entry->user_id);

                $entry->delete();

                $this->enrollInMoodleCourses($entry->user, $entry->course->moodle_course_ids);
            });

            // Notify user
            $apiKey = config('services.vatger.api_key');

            if (!$apiKey) {
                Log::warning('VATGER API key not configured, skipping notification');
            }

            $message = sprintf(
                "You have been enrolled in the %s course. Check the training centre for moodle 
        courses to start your training.",
                $entry->course->name
            );

            $data = [
                'title' => 'Start of Training',
                'message' => $message,
                'source_name' => 'VATGER ATD',
                'link_text' => 'Training Centre',
                'link_url' => 'https://training.vatsim-germany.org',
                'via' => 'board.ping',
            ];

            $headers = [
                'Authorization' => "Token {$apiKey}",
            ];

            $response = \Http::withHeaders($headers)
                ->post("https://vatsim-germany.org/api/user/{$entry->user->vatsim_id}/send_notification", $data);

            if (!$response->successful()) {
                throw new \Exception("Failed to send notification: " . $response->body());
            }

            Log::info('Training started', [
                'mentor_id' => $mentor->id,
                'trainee_id' => $entry->user_id,
                'course_id' => $entry->course_id,
                'course_name' => $entry->course->name
            ]);

            return [true, 'Training started successfully.'];
        } catch (\Exception $e) {
            Log::error('Failed to start training', [
                'entry_id' => $entry->id,
                'mentor_id' => $mentor->id,
                'error' => $e->getMessage()
            ]);

            return [false, 'Failed to start training. Please try again.'];
        }
    }

    /**
     * Update remarks for a waiting list entry
     */
    public function updateRemarks(WaitingListEntry $entry, string $remarks, User $mentor): array
    {
        try {
            $entry->update(['remarks' => $remarks]);

            Log::info('Waiting list remarks updated', [
                'entry_id' => $entry->id,
                'mentor_id' => $mentor->id,
                'trainee_id' => $entry->user_id,
                'course_id' => $entry->course_id
            ]);

            return [true, 'Remarks updated successfully.'];
        } catch (\Exception $e) {
            Log::error('Failed to update remarks', [
                'entry_id' => $entry->id,
                'mentor_id' => $mentor->id,
                'error' => $e->getMessage()
            ]);

            return [false, 'Failed to update remarks. Please try again.'];
        }
    }

    /**
     * Enroll user in Moodle courses
     */
    protected function enrollInMoodleCourses(User $user, array $courseIds): void
    {
        if (empty($courseIds)) {
            return;
        }

        $apiKey = config('services.vatger.api_key');
        if (!$apiKey) {
            Log::warning('VATGER API key not configured, skipping Moodle enrollment');
            return;
        }

        foreach ($courseIds as $courseId) {
            try {
                \Http::withHeaders([
                    'Authorization' => "Token {$apiKey}",
                ])->get("https://vatsim-germany.org/api/moodle/course/{$courseId}/user/{$user->vatsim_id}/enrol");
            } catch (\Exception $e) {
                Log::warning('Failed to enroll user in Moodle course', [
                    'user_id' => $user->id,
                    'vatsim_id' => $user->vatsim_id,
                    'course_id' => $courseId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}