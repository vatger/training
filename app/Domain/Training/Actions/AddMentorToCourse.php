<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\MentorAdded;
use App\Models\Course;
use App\Models\User;

class AddMentorToCourse
{
    public function execute(Course $course, User $mentorToAdd, User $addingUser): void
    {
        $course->mentors()->attach($mentorToAdd->id);

        event(new MentorAdded($course, $mentorToAdd, $addingUser));
    }
}
