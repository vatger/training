<?php

namespace App\Services;

use App\Models\Cpt;
use App\Models\Examiner;
use App\Models\User;
use App\Integrations\Vatger\VatgerClientInterface;

class CptNotificationService
{
    public function __construct(
        private readonly VatgerClientInterface $vatger,
    ) {}

    public function broadcastConfirmedCpts(): void
    {
        if (app()->environment() !== 'production') {
            return;
        }

        $cpts = Cpt::with(['trainee', 'course'])
            ->where('confirmed', true)
            ->whereNull('passed')
            ->orderBy('date')
            ->get();

        if ($cpts->isEmpty()) {
            return;
        }

        $cptData = $cpts->map(fn ($cpt) => [
            'trainee'  => $cpt->trainee->full_name,
            'date'     => $cpt->date->format('d.m.y H:i') . 'lcl',
            'position' => $cpt->course->solo_station,
        ])->toArray();

        $this->vatger->postConfirmedCpts($cptData);
    }

    public function notifyAvailableCpt(Cpt $cpt): void
    {
        $notifyUsers = collect();

        if (!$cpt->examiner_id) {
            Examiner::with('user')
                ->whereJsonContains('positions', $cpt->course->position)
                ->get()
                ->each(fn ($e) => $notifyUsers->push($e->user));
        }

        if (!$cpt->local_id) {
            $cpt->course->load('mentors');
            $cpt->course->mentors->each(fn ($m) => $notifyUsers->push($m));
        }

        $notifyUsers->unique('id')->each(function (User $user) use ($cpt) {
            if ($user->vatsim_id) {
                $this->vatger->sendNotification(
                    vatsimId: $user->vatsim_id,
                    title: 'Available CPT',
                    message: "A new CPT is available: {$cpt->course->solo_station} on {$cpt->date->format('d.m.Y')} at {$cpt->date->format('H:i')}lcl",
                    linkText: 'Training Centre',
                    linkUrl: route('cpt.index'),
                );
            }
        });
    }

    public function notifyUnassignment(Cpt $cpt, string $role, User $unassignedUser): void
    {
        $cpt->load(['trainee', 'course', 'examiner', 'local']);

        $notifyUsers = collect();

        if ($cpt->examiner_id && $cpt->examiner_id !== $unassignedUser->id) {
            $notifyUsers->push($cpt->examiner);
        }

        if ($cpt->local_id && $cpt->local_id !== $unassignedUser->id) {
            $notifyUsers->push($cpt->local);
        }

        $creator = User::find($cpt->created_by ?? null);
        if ($creator && $creator->id !== $unassignedUser->id) {
            $notifyUsers->push($creator);
        }

        $roleText  = $role === 'examiner' ? 'examiner' : 'local contact';
        $message   = "{$unassignedUser->full_name} has unassigned themselves as {$roleText} from the CPT for {$cpt->trainee->full_name} at {$cpt->course->solo_station} on {$cpt->date->format('d.m.Y')} at {$cpt->date->format('H:i')}lcl";

        $notifyUsers->unique('id')->each(function (User $user) use ($message) {
            if ($user->vatsim_id) {
                $this->vatger->sendNotification(
                    vatsimId: $user->vatsim_id,
                    title: 'CPT Assignment Changed',
                    message: $message,
                    linkText: 'View CPT',
                    linkUrl: route('cpt.index'),
                );
            }
        });
    }
}