<?php

namespace App\Domain\Endorsement\Actions;

use App\Domain\Endorsement\Events\Tier2EndorsementGranted;
use App\Integrations\VatEud\VatEudService;
use App\Integrations\Moodle\MoodleClientInterface;
use App\Models\Tier2Endorsement;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class GrantTier2Endorsement
{
    public function __construct(
        private VatEudService $vatEudService,
        private MoodleClientInterface $moodle,
    ) {}

    public function execute(Tier2Endorsement $tier2Endorsement, User $trainee): void
    {
        $existing = collect($this->vatEudService->getTier2Endorsements())
            ->where('user_cid', $trainee->vatsim_id)
            ->where('position', $tier2Endorsement->position)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'endorsement' => 'You already have this endorsement.',
            ]);
        }

        if ($tier2Endorsement->moodle_course_id) {
            $completed = $this->moodle->getCourseCompletion(
                $trainee->vatsim_id,
                $tier2Endorsement->moodle_course_id
            );

            if (! $completed) {
                throw ValidationException::withMessages([
                    'endorsement' => 'You must complete the Moodle course before requesting this endorsement.',
                ]);
            }
        }

        $success = $this->vatEudService->createTier2Endorsement(
            $trainee->vatsim_id,
            $tier2Endorsement->position,
            config('services.vateud.atd_lead_cid', 1441619)
        );

        if (! $success) {
            throw new \RuntimeException('Failed to create endorsement via VatEUD API.');
        }

        Cache::forget('vateud:tier2_endorsements');

        event(new Tier2EndorsementGranted($tier2Endorsement, $trainee));
    }
}