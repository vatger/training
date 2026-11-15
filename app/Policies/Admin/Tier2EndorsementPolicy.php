<?php

namespace App\Policies\Admin;

class Tier2EndorsementPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'tier2_endorsements';
    }
}
