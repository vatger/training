<?php

namespace App\Domain\WaitingList\Actions;

use App\Domain\WaitingList\Events\WaitingListInterestConfirmed;
use App\Models\User;
use App\Models\WaitingListEntry;

class ConfirmWaitingListInterest
{
    public function execute(WaitingListEntry $entry, User $user): void
    {
        $entry->is_interested = true;
        $entry->interest_confirmed_at = now();
        $entry->removal_date = null;
        $entry->save();

        event(new WaitingListInterestConfirmed($entry, $entry->course, $user));
    }
}
