<?php

namespace App\Domain\Training\Events;

use App\Models\Course;
use App\Models\User;

readonly class TraineeRemarkUpdated
{
    public function __construct(
        public Course  $course,
        public User    $trainee,
        public User    $mentor,
        public string  $newRemarks,
    ) {}
}