<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\MentorRemoved;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RemoveMentorFromCourse
{
    public function execute(Course $course, User $mentorToRemove, User $removingUser): void
    {
        $course->mentors()->detach($mentorToRemove->id);

        DB::table('course_trainees')
            ->where('course_id', $course->id)
            ->where('claimed_by_mentor_id', $mentorToRemove->id)
            ->update(['claimed_by_mentor_id' => null, 'claimed_at' => null]);

        event(new MentorRemoved($course, $mentorToRemove, $removingUser));
    }
}