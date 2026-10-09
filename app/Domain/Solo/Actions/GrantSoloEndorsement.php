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

    public const MAX_SOLO_DAYS = 90;

    public const MIN_SOLO_DURATION_DAYS = 7;

    public function __construct(
        private readonly VatEudService $vatEud,
        private readonly MoodleClientInterface $moodle,
    ) {}

    public function execute(Course $course, User $trainee, User $mentor, Carbon $expiryDate): void
    {
        $this->assertMoodleComplete($trainee, $course);
        $this->assertCoreTheoryPassed($trainee, $course);
        $this->assertNoExistingSolo($trainee, $course);
        $this->assertWithinSoloDayBudget($trainee, $expiryDate);

        $formattedExpiry = $expiryDate->setTime(23, 59, 0)->format('Y-m-d\TH:i:s.v\Z');

        $result = $this->vatEud->createSoloEndorsement(
            $trainee->vatsim_id,
            $course->solo_station,
            $formattedExpiry,
            $mentor->vatsim_id,
        );

        if (! $result['success']) {
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
            if (! $this->moodle->getCourseCompletion($trainee->vatsim_id, $moodleCourseId)) {
                throw ValidationException::withMessages([
                    'error' => 'Trainee has not completed all required Moodle courses',
                ]);
            }
        }
    }

    private function assertCoreTheoryPassed(User $trainee, Course $course): void
    {
        if (! isset(self::CORE_THEORY_IDS[$course->position])) {
            return;
        }

        $examId = self::CORE_THEORY_IDS[$course->position];
        $exams = $this->vatEud->getUserExams($trainee->vatsim_id);
        $passed = collect($exams->results)
            ->filter(fn ($r) => $r->examId === $examId && $r->passed && $r->expiry->isFuture())
            ->isNotEmpty();

        if (! $passed) {
            throw ValidationException::withMessages([
                'error' => 'Trainee has not passed the required core theory test',
            ]);
        }
    }

    private function assertNoExistingSolo(User $trainee, Course $course): void
    {
        $existing = collect($this->vatEud->getSoloEndorsements())->first(
            fn ($s) => $s->userCid === $trainee->vatsim_id && $s->position === $course->solo_station,
        );

        if ($existing) {
            throw ValidationException::withMessages([
                'error' => 'Trainee already has a solo endorsement for this position',
            ]);
        }
    }

    private function assertWithinSoloDayBudget(User $trainee, Carbon $expiryDate): void
    {
        $used = $trainee->solo_days_used ?? 0;
        $remaining = self::MAX_SOLO_DAYS - $used;

        if ($remaining <= 0) {
            throw ValidationException::withMessages([
                'error' => "Trainee has already used {$used}/".self::MAX_SOLO_DAYS.' solo days allowed at this rating (GCAP 7.3c). No further solo endorsement can be issued until they are upgraded to the next rating.',
            ]);
        }

        $requestedDays = Carbon::now()->startOfDay()->diffInDays($expiryDate->copy()->startOfDay());
        $maxExpiry = Carbon::now()->startOfDay()->addDays($remaining)->format('Y-m-d');

        if ($requestedDays > $remaining) {
            throw ValidationException::withMessages([
                'error' => "This expiry date would exceed the 90-day GCAP solo limit for this rating. Trainee has used {$used}/".self::MAX_SOLO_DAYS." days, so only {$remaining} day(s) remain. Maximum expiry date is {$maxExpiry}.",
            ]);
        }

        // Granting less than the full remaining budget while leaving fewer than the
        // 7-day minimum spare would strand that leftover permanently: no future solo
        // could ever use it, since every solo must run at least 7 days (GCAP 7.3c).
        $leftover = $remaining - $requestedDays;

        if ($leftover > 0 && $leftover < self::MIN_SOLO_DURATION_DAYS) {
            throw ValidationException::withMessages([
                'error' => "Granting a {$requestedDays}-day solo would leave only {$leftover} day(s) of the 90-day GCAP budget remaining — below the 7-day minimum for any future solo, so that time could never be used. Either grant the entire {$remaining} remaining day(s) (expiry {$maxExpiry}), or pick a duration that leaves at least ".self::MIN_SOLO_DURATION_DAYS.' day(s) spare.',
            ]);
        }
    }
}
