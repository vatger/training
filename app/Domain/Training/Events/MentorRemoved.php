<?php

namespace App\Domain\Training\Events;

use App\Models\Course;
use App\Models\User;

readonly class MentorRemoved
{
    public function __construct(
        public Course $course,
        public User $removedMentor,
        public User $removingUser,
    ) {}
}
