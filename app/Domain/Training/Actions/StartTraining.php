<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\TrainingStarted;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StartTraining
{
    public function execute(WaitingListEntry $entry, User $mentor): array
    {
        $minActivity = config('services.training.display_activity', 8);

        if ($entry->course->type === 'RTG' && $entry->activity < $minActivity) {
            return [false, 'Trainee does not have sufficient activity to start training.'];
        }

        try {
            DB::transaction(function () use ($entry) {
                $entry->course->activeTrainees()->attach($entry->user_id);
                $entry->delete();
                $this->enrollInMoodle($entry->user, $entry->course->moodle_course_ids ?? []);
            });

            $this->sendStartNotification($entry);

            event(new TrainingStarted($entry->course, $entry->user, $mentor, $entry));

            return [true, 'Training started successfully.'];
        } catch (\Exception $e) {
            Log::error('Failed to start training', [
                'entry_id'  => $entry->id,
                'mentor_id' => $mentor->id,
                'error'     => $e->getMessage(),
            ]);

            return [false, 'Failed to start training. Please try again.'];
        }
    }

    private function enrollInMoodle(User $user, array $courseIds): void
    {
        if (empty($courseIds)) {
            return;
        }

        $apiKey     = config('services.vatger.api_key');
        $apiBaseUrl = config('services.vatger.api_url');

        if (!$apiKey) {
            Log::warning('VATGER API key not configured, skipping Moodle enrollment');
            return;
        }

        foreach ($courseIds as $courseId) {
            try {
                \Http::withHeaders(['Authorization' => "Token {$apiKey}"])
                    ->get("{$apiBaseUrl}/moodle/course/{$courseId}/user/{$user->vatsim_id}/enrol");
            } catch (\Exception $e) {
                Log::warning('Failed to enroll user in Moodle course', [
                    'user_id'   => $user->id,
                    'course_id' => $courseId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendStartNotification(WaitingListEntry $entry): void
    {
        $apiKey     = config('services.vatger.api_key');
        $apiBaseUrl = config('services.vatger.api_url');

        if (!$apiKey) {
            Log::warning('VATGER API key not configured, skipping notification');
            return;
        }

        $response = \Http::withHeaders(['Authorization' => "Token {$apiKey}"])
            ->post("{$apiBaseUrl}/user/{$entry->user->vatsim_id}/send_notification", [
                'title'       => 'Start of Training',
                'message'     => "You have been enrolled in the {$entry->course->name} course. Check the training centre for moodle courses to start your training.",
                'source_name' => 'VATGER ATD',
                'link_text'   => 'Training Centre',
                'link_url'    => 'https://training.vatsim-germany.org',
                'via'         => 'board.ping',
            ]);

        if (!$response->successful()) {
            Log::warning('Failed to send training start notification', [
                'trainee_id' => $entry->user_id,
                'response'   => $response->body(),
            ]);
        }
    }
}