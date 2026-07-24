<?php

namespace App\Domain\Gdpr\Actions;

use App\Models\RosterEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Scrubs a user's personal data in place instead of deleting the row.
 *
 * Every other model (training logs, cpts, waiting list entries, roles, ...)
 * keeps referencing this user by id, so nothing referenced elsewhere is
 * deleted or orphaned - it will simply display as "Deleted User" wherever
 * the user's name is shown, since first_name/last_name are overwritten below.
 */
class AnonymizeUser
{
    public function execute(User $user): void
    {
        $vatsimId = $user->vatsim_id;

        $user->forceFill([
            'first_name' => 'Deleted',
            'last_name' => 'User',
            'email' => null,
            'email_verified_at' => null,
            'password' => Hash::make(Str::random(40)),
            'remember_token' => null,
            'vatsim_id' => null,
            'is_staff' => false,
            'is_superuser' => false,
            'is_admin' => false,
            'gdpr_deleted_at' => now(),
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        if ($vatsimId !== null) {
            RosterEntry::where('user_id', $vatsimId)->delete();
        }
    }
}
