<?php

namespace App\Domain\Solo\Actions;

use App\Domain\Solo\Events\SoloRemoved;
use App\Models\Course;
use App\Models\User;
use App\Services\VatEudService;
use Illuminate\Validation\ValidationException;

class RemoveSoloEndorsement
{
    public function __construct(
        private VatEudService $vatEudService,
    ) {}

    public function execute(Course $course, User $trainee, User $mentor): void
    {
        $solo = collect($this->vatEudService->getSoloEndorsements())->first(
            fn($s) => $s['user_cid'] == $trainee->vatsim_id && $s['position'] === $course->solo_station,
        );

        if (!$solo) {
            throw ValidationException::withMessages([
                'error' => 'No solo endorsement found for this trainee and position',
            ]);
        }

        $success = $this->vatEudService->removeSoloEndorsement($solo['id']);

        if (!$success) {
            throw ValidationException::withMessages([
                'error' => 'Failed to remove solo endorsement',
            ]);
        }

        $this->vatEudService->refreshEndorsementCache();

        event(new SoloRemoved($course, $trainee, $mentor, $course->solo_station));
    }
}