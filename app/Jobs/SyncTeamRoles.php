<?php

namespace App\Jobs;

use App\Integrations\Vatger\VatgerClientInterface;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Re-syncs team roles and staff flags from VATGER for users who hold any, so removals
 * show up without the user having to log in again.
 *
 * Intentionally not queued: the scheduler runs it inline, so no queue worker is required.
 */
class SyncTeamRoles
{
    use Dispatchable;

    private const MENTOR_TEAMS = ['EDGG Mentor', 'EDMM Mentor', 'EDWW Mentor'];

    private const LEADERSHIP_TEAMS = ['ATD Leitung', 'VATGER Leitung'];

    public function __construct(private readonly ?int $vatsimId = null) {}

    public function handle(VatgerClientInterface $vatger): void
    {
        $users = User::query()
            ->when($this->vatsimId, fn ($query, $id) => $query->where('vatsim_id', $id))
            ->when(! $this->vatsimId, fn ($query) => $query
                ->where(fn ($query) => $query
                    ->whereHas('roles')
                    ->orWhere('is_staff', true)
                    ->orWhere('is_superuser', true)))
            ->get();

        foreach ($users as $user) {
            $teams = $vatger->getUserTeams($user->vatsim_id);

            // Never strip access on a failed lookup; keep the current state and retry next run.
            if ($teams === null) {
                Log::warning('Could not fetch teams, skipping role sync', ['vatsim_id' => $user->vatsim_id]);

                continue;
            }

            $this->sync($user, $teams);
        }
    }

    private function sync(User $user, array $teams): void
    {
        $isLeadership = ! empty(array_intersect(self::LEADERSHIP_TEAMS, $teams));
        $isStaff = $isLeadership || ! empty(array_intersect(self::MENTOR_TEAMS, $teams));

        $result = $user->roles()->sync(Role::whereIn('name', $teams)->pluck('id')->all());

        $user->is_staff = $isStaff;
        $user->is_superuser = $isLeadership;
        $flagsChanged = $user->isDirty(['is_staff', 'is_superuser']);
        $user->save();

        if ($result['attached'] || $result['detached'] || $flagsChanged) {
            Log::info('Synced team roles', ['vatsim_id' => $user->vatsim_id, 'teams' => $teams]);
        }
    }
}
