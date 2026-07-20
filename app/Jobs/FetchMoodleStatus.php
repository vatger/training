<?php

namespace App\Jobs;

use App\Integrations\Moodle\MoodleClient;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchMoodleStatus implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(
        private int $traineeId,
        private int $courseId,
    ) {}

    public static function cacheKey(int $vatsimId, int $courseId): string
    {
        return "moodle_status_{$vatsimId}_{$courseId}";
    }

    public static function pendingKey(int $vatsimId, int $courseId): string
    {
        return "moodle_status_pending_{$vatsimId}_{$courseId}";
    }

    public function handle(MoodleClient $moodleClient): void
    {
        $trainee = User::find($this->traineeId);
        $course = Course::find($this->courseId);

        if (! $trainee || ! $course) {
            return;
        }

        $cacheKey = self::cacheKey($trainee->vatsim_id, $course->id);

        try {
            Cache::put($cacheKey, $this->resolveStatus($moodleClient, $trainee->vatsim_id, $course->moodle_course_ids), 300);
        } catch (\Exception $e) {
            Log::error('FetchMoodleStatus job failed', [
                'trainee_id' => $this->traineeId,
                'course_id' => $this->courseId,
                'error' => $e->getMessage(),
            ]);

            Cache::forget($cacheKey);
        }
    }

    private function resolveStatus(MoodleClient $moodleClient, int $vatsimId, array $moodleCourseIds): string
    {
        if (! $moodleClient->userExists($vatsimId)) {
            return 'not-started';
        }

        $allCompleted = collect($moodleCourseIds)->every(
            fn ($courseId) => $moodleClient->getCourseCompletion($vatsimId, $courseId)
        );

        return $allCompleted ? 'completed' : 'in-progress';
    }
}
