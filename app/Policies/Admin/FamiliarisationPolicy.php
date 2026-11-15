<?php

namespace App\Policies\Admin;

class FamiliarisationPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'familiarisations';
    }
}
