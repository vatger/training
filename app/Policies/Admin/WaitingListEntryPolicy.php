<?php

namespace App\Policies\Admin;

class WaitingListEntryPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'waiting_list_entries';
    }
}
