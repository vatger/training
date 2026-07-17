<?php

namespace App\Domain\Training\Events;

use App\Models\Course;
use App\Models\User;

readonly class MentorAdded
{
    public function __construct(
        public Course $course,
        public User   $newMentor,
        public User   $addingUser,
    ) {}
}