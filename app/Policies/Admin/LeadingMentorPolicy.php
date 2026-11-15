<?php

namespace App\Policies\Admin;

class LeadingMentorPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'leading_mentors';
    }
}
