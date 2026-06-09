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
            fn($s) => $s->userCid === $trainee->vatsim_id && $s->position === $course->solo_station,
        );

        if (!$solo) {
            throw ValidationException::withMessages([
                'error' => 'No solo endorsement found for this trainee and position',
            ]);
        }

        $this->vatEud->deleteSoloEndorsement($solo->id);

        $formattedExpiry = $expiryDate->setTime(23, 59, 0)->format('Y-m-d\TH:i:s.v\Z');

        $result = $this->vatEud->createSoloEndorsement(
            $trainee->vatsim_id,
            $course->solo_station,
            $formattedExpiry,
            $mentor->vatsim_id,
        );

        if (!$result['success']) {
            throw ValidationException::withMessages([
                'error' => $result['message'] ?? 'Failed to extend solo endorsement',
            ]);
        }

        $this->vatEud->refreshEndorsementCache();

        event(new SoloExtended($course, $trainee, $mentor, $course->solo_station, $formattedExpiry));
    }
}