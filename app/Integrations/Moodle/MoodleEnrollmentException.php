<?php

namespace App\Integrations\Moodle;

class MoodleEnrollmentException extends \RuntimeException
{
    /**
     * @param  array<int, int>  $courseIds  the Moodle courses the user could not be enrolled in
     */
    public static function forCourses(int $vatsimId, array $courseIds): self
    {
        return new self(sprintf(
            'The trainee (VATSIM ID %d) could not be enrolled in the Moodle course(s) %s. Please make sure they have logged in to Moodle at least once and try again.',
            $vatsimId,
            implode(', ', $courseIds),
        ));
    }
}
