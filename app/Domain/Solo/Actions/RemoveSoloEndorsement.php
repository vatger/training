<?php

namespace App\Domain\Solo\Actions;

use App\Domain\Solo\Events\SoloRemoved;
use App\Integrations\VatEud\VatEudService;
use App\Models\Course;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RemoveSoloEndorsement
{
    public function __construct(
        private readonly VatEudService $vatEud,
    ) {}

    public function execute(Course $course, User $trainee, User $mentor): void
    {
        $solo = collect($this->vatEud->getSoloEndorsements())->first(
            fn($s) => $s->userCid === $trainee->vatsim_id && $s->position === $course->solo_station,
        );

        if (!$solo) {
            throw ValidationException::withMessages([
                'error' => 'No solo endorsement found for this trainee and position',
            ]);
        }

        $success = $this->vatEud->deleteSoloEndorsement($solo->id);

        if (!$success) {
            throw ValidationException::withMessages([
                'error' => 'Failed to remove solo endorsement',
            ]);
        }

        $this->vatEud->refreshEndorsementCache();

        event(new SoloRemoved($course, $trainee, $mentor, $course->solo_station));
    }
}