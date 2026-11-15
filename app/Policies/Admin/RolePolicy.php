<?php

namespace App\Policies\Admin;

class RolePolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'roles';
    }
}
