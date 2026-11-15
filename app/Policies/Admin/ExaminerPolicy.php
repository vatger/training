<?php

namespace App\Policies\Admin;

class ExaminerPolicy extends AdminResourcePolicy
{
    protected function resource(): string
    {
        return 'examiners';
    }
}
