<?php

namespace App\Domain\Solo\Actions;

use App\Domain\Solo\Events\SoloExtended;
use App\Integrations\VatEud\VatEudService;
use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ExtendSoloEndorsement
{
    public function __construct(
        private readonly VatEudService $vatEud,
    ) {}

    public function execute(Course $course, User $trainee, User $mentor, Carbon $expiryDate): void
    {
        $solo = collect($this->vatEud->getSoloEndorsements())->first(
            fn ($s) => $s->userCid === $trainee->vatsim_id && $s->position === $course->solo_station,
        );

        if (! $solo) {
            throw ValidationException::withMessages([
                'error' => 'No solo endorsement found for this trainee and position',
            ]);
        }

        $this->assertWithinSoloDayBudget($trainee, $expiryDate);

        $this->vatEud->deleteSoloEndorsement($solo->id);

        $formattedExpiry = $expiryDate->setTime(23, 59, 0)->format('Y-m-d\TH:i:s.v\Z');

        $result = $this->vatEud->createSoloEndorsement(
            $trainee->vatsim_id,
            $course->solo_station,
            $formattedExpiry,
            $mentor->vatsim_id,
        );

        if (! $result['success']) {
            throw ValidationException::withMessages([
                'error' => $result['message'] ?? 'Failed to extend solo endorsement',
            ]);
        }

        $this->vatEud->refreshEndorsementCache();

        event(new SoloExtended($course, $trainee, $mentor, $course->solo_station, $formattedExpiry));
    }

    private function assertWithinSoloDayBudget(User $trainee, Carbon $expiryDate): void
    {
        $used = $trainee->solo_days_used ?? 0;
        $remaining = GrantSoloEndorsement::MAX_SOLO_DAYS - $used;

        if ($remaining <= 0) {
            throw ValidationException::withMessages([
                'error' => "Trainee has already used {$used}/".GrantSoloEndorsement::MAX_SOLO_DAYS.' solo days allowed at this rating (GCAP 7.3c). The solo endorsement cannot be extended further until they are upgraded to the next rating.',
            ]);
        }

        $requestedDays = Carbon::now()->startOfDay()->diffInDays($expiryDate->copy()->startOfDay());

        if ($requestedDays > $remaining) {
            $maxExpiry = Carbon::now()->startOfDay()->addDays($remaining)->format('Y-m-d');

            throw ValidationException::withMessages([
                'error' => "This expiry date would exceed the 90-day GCAP solo limit for this rating. Trainee has used {$used}/".GrantSoloEndorsement::MAX_SOLO_DAYS." days, so only {$remaining} day(s) remain. Maximum expiry date is {$maxExpiry}.",
            ]);
        }
    }
}
