<?php

namespace App\Policies\Admin;

class WaitingListRestrictionPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'users';
    }
}
