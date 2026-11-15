<?php

namespace App\Policies\Admin;

class FamiliarisationSectorPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'familiarisation_sectors';
    }
}
