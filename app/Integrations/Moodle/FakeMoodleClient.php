<?php

namespace App\Integrations\Moodle;

class FakeMoodleClient implements MoodleClientInterface
{
    public function userExists(int $vatsimId): bool
    {
        return true;
    }

    public function getCourseCompletion(int $vatsimId, int $courseId): bool
    {
        return false;
    }

    public function getCourseName(int $courseId): ?string
    {
        return "Moodle Course {$courseId}";
    }

    public function enrollUser(int $vatsimId, int $courseId): bool
    {
        return true;
    }
}