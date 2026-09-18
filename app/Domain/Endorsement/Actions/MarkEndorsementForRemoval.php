<?php

namespace App\Domain\Endorsement\Actions;

use App\Domain\Endorsement\Events\EndorsementMarkedForRemoval;
use App\Integrations\VatEud\VatEudService;
use App\Models\EndorsementActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class MarkEndorsementForRemoval
{
    public function __construct(
        private readonly VatEudService $vatEud,
    ) {}

    public function execute(EndorsementActivity $activity, User $actor): void
    {
        if ($activity->removal_date) {
            throw ValidationException::withMessages([
                'endorsement' => 'This endorsement is already marked for removal.',
            ]);
        }

        $endorsement = collect($this->vatEud->getTier1Endorsements())
            ->first(fn ($e) => $e->id === $activity->endorsement_id);

        if (! $endorsement || $endorsement->createdAt->gt(now()->subMonths(6))) {
            throw ValidationException::withMessages([
                'endorsement' => 'Endorsement must be at least 6 months old before it can be removed.',
            ]);
        }

        $minRequiredMinutes = config('services.vateud.min_activity_minutes', 180);

        if ($activity->activity_minutes >= $minRequiredMinutes) {
            throw ValidationException::withMessages([
                'endorsement' => 'Endorsement has sufficient activity and cannot be marked for removal.',
            ]);
        }

        $activity->removal_date = Carbon::now()->addDays(config('services.vateud.removal_warning_days', 31));
        $activity->removal_notified = false;
        $activity->last_updated = Carbon::createFromTimestamp(1);
        $activity->save();

        $trainee = User::where('vatsim_id', $activity->vatsim_id)->first();

        event(new EndorsementMarkedForRemoval($activity, $actor, $trainee));
    }
}
