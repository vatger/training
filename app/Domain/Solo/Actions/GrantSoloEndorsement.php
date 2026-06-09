<?php

namespace App\Domain\Solo\Actions;

use App\Domain\Solo\Events\SoloGranted;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Integrations\VatEud\VatEudService;
use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class GrantSoloEndorsement
{
    private const CORE_THEORY_IDS = ['GND' => 6, 'TWR' => 9, 'APP' => 10, 'CTR' => 11];

    public function __construct(
        private readonly VatEudService $vatEud,
        private readonly MoodleClientInterface $moodle,
    ) {}

    public function execute(Course $course, User $trainee, User $mentor, Carbon $expiryDate): void
    {
        $this->assertMoodleComplete($trainee, $course);
        $this->assertCoreTheoryPassed($trainee, $course);
        $this->assertNoExistingSolo($trainee, $course);

        $formattedExpiry = $expiryDate->setTime(23, 59, 0)->format('Y-m-d\TH:i:s.v\Z');

        $result = $this->vatEud->createSoloEndorsement(
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

        $this->vatEud->refreshEndorsementCache();

        event(new SoloGranted($course, $trainee, $mentor, $course->solo_station, $formattedExpiry));
    }

    private function assertMoodleComplete(User $trainee, Course $course): void
    {
        if (empty($course->moodle_course_ids)) {
            return;
        }

        foreach ($course->moodle_course_ids as $moodleCourseId) {
            if (!$this->moodle->getCourseCompletion($trainee->vatsim_id, $moodleCourseId)) {
                throw ValidationException::withMessages([
                    'error' => 'Trainee has not completed all required Moodle courses',
                ]);
            }
        }
    }

    private function assertCoreTheoryPassed(User $trainee, Course $course): void
    {
        if (!isset(self::CORE_THEORY_IDS[$course->position])) {
            return;
        }

        $examId = self::CORE_THEORY_IDS[$course->position];
        $exams = $this->vatEud->getUserExams($trainee->vatsim_id);
        $passed = collect($exams->results)
            ->filter(fn($r) => $r->examId === $examId && $r->passed && $r->expiry->isFuture())
            ->isNotEmpty();

        if (!$passed) {
            throw ValidationException::withMessages([
                'error' => 'Trainee has not passed the required core theory test',
            ]);
        }
    }

    private function assertNoExistingSolo(User $trainee, Course $course): void
    {
        $existing = collect($this->vatEud->getSoloEndorsements())->first(
            fn($s) => $s->userCid === $trainee->vatsim_id && $s->position === $course->solo_station,
        );

        if ($existing) {
            throw ValidationException::withMessages([
                'error' => 'Trainee already has a solo endorsement for this position',
            ]);
        }
    }
}