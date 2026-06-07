<?php

namespace App\Domain\Solo\Actions;

use App\Domain\Solo\Events\SoloGranted;
use App\Models\Course;
use App\Models\User;
use App\Services\MoodleService;
use App\Services\VatEudService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class GrantSoloEndorsement
{
    public function __construct(
        private VatEudService $vatEudService,
        private MoodleService $moodleService,
    ) {}

    public function execute(Course $course, User $trainee, User $mentor, Carbon $expiryDate): void
    {
        $this->assertMoodleComplete($trainee, $course);
        $this->assertCoreTheoryPassed($trainee, $course);
        $this->assertNoExistingSolo($trainee, $course);

        $formattedExpiry = $expiryDate->setTime(23, 59, 0)->format('Y-m-d\TH:i:s.v\Z');

        $result = $this->vatEudService->createSoloEndorsement(
            $trainee->vatsim_id,
            $course->solo_station,
            $formattedExpiry,
            $mentor->vatsim_id,
        );

        if (!$result['success']) {
            throw ValidationException::withMessages([
                'error' => $result['message'] ?? 'Failed to grant solo endorsement',
            ]);
        }

        $this->vatEudService->refreshEndorsementCache();

        event(new SoloGranted($course, $trainee, $mentor, $course->solo_station, $formattedExpiry));
    }

    private function assertMoodleComplete(User $trainee, Course $course): void
    {
        if (empty($course->moodle_course_ids)) {
            return;
        }

        foreach ($course->moodle_course_ids as $moodleCourseId) {
            if (!$this->moodleService->getCourseCompletion($trainee->vatsim_id, $moodleCourseId)) {
                throw ValidationException::withMessages([
                    'error' => 'Trainee has not completed all required Moodle courses',
                ]);
            }
        }
    }

    private function assertCoreTheoryPassed(User $trainee, Course $course): void
    {
        $status = $this->resolveCoreTheoryStatus($trainee, $course);

        if (!in_array($status, ['passed', 'not_required'])) {
            throw ValidationException::withMessages([
                'error' => 'Trainee has not passed the required core theory test',
            ]);
        }
    }

    private function assertNoExistingSolo(User $trainee, Course $course): void
    {
        $existing = collect($this->vatEudService->getSoloEndorsements())->first(
            fn($s) => $s['user_cid'] == $trainee->vatsim_id && $s['position'] === $course->solo_station,
        );

        if ($existing) {
            throw ValidationException::withMessages([
                'error' => 'Trainee already has a solo endorsement for this position',
            ]);
        }
    }

    private function resolveCoreTheoryStatus(User $trainee, Course $course): string
    {
        $coreTheoryIds = ['GND' => 6, 'TWR' => 9, 'APP' => 10, 'CTR' => 11];

        if (!isset($coreTheoryIds[$course->position])) {
            return 'not_required';
        }

        $examId = $coreTheoryIds[$course->position];
        $exams  = $this->vatEudService->getUserExams($trainee->vatsim_id);

        $passed = collect($exams['results'] ?? [])
            ->where('exam_id', $examId)
            ->where('passed', true)
            ->filter(fn($e) => Carbon::parse($e['expiry'])->isFuture());

        if ($passed->isNotEmpty()) {
            return 'passed';
        }

        return 'not_passed';
    }
}