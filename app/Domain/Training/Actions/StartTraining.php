<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\TrainingStarted;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StartTraining
{
    public function __construct(
        private readonly MoodleClientInterface $moodle,
        private readonly VatgerClientInterface $vatger,
    ) {}

    public function execute(WaitingListEntry $entry, User $mentor): array
    {
        $minActivity = config('services.training.display_activity', 8);

        if ($entry->course->type === 'RTG' && $entry->course->position !== 'CTR' && $entry->activity < $minActivity) {
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
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'course_trainee_unique')) {
                Log::error('Failed to start training: trainee already has a record for this course', [
                    'entry_id' => $entry->id,
                    'user_id' => $entry->user_id,
                    'course_id' => $entry->course_id,
                    'mentor_id' => $mentor->id,
                    'error' => $e->getMessage(),
                ]);

                return [false, 'This trainee already has an existing (completed or removed) record for this course. An administrator needs to resolve this manually before training can be restarted.'];
            }

            Log::error('Failed to start training due to a database error', [
                'entry_id' => $entry->id,
                'user_id' => $entry->user_id,
                'course_id' => $entry->course_id,
                'mentor_id' => $mentor->id,
                'error' => $e->getMessage(),
            ]);

            return [false, 'Failed to start training due to a database error. Please try again or contact an administrator.'];
        } catch (\Exception $e) {
            Log::error('Failed to start training', [
                'entry_id' => $entry->id,
                'user_id' => $entry->user_id,
                'course_id' => $entry->course_id,
                'mentor_id' => $mentor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [false, 'Failed to start training. Please try again or contact an administrator if the problem persists.'];
        }
    }

    private function enrollInMoodle(User $user, array $courseIds): void
    {
        foreach ($courseIds as $courseId) {
            if (! $this->moodle->enrollUser($user->vatsim_id, (int) $courseId)) {
                Log::warning('Failed to enroll user in Moodle course', [
                    'user_id' => $user->id,
                    'course_id' => $courseId,
                ]);
            }
        }
    }

    private function sendStartNotification(WaitingListEntry $entry): void
    {
        $result = $this->vatger->sendNotification(
            $entry->user->vatsim_id,
            'Start of Training',
            "You have been enrolled in the {$entry->course->name} course. Check the training centre for moodle courses to start your training.",
            'vatger ATD',
            'https://training.vatsim-germany.org',
            'Training Centre',
        );

        if (! ($result['success'] ?? false)) {
            Log::warning('Failed to send training start notification', ['trainee_id' => $entry->user_id]);
        }
    }
}
