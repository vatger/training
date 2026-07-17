<?php

namespace App\Integrations\Moodle;

interface MoodleClientInterface
{
  public function userExists(int $vatsimId): bool;

  public function getCourseCompletion(int $vatsimId, int $courseId): bool;

  public function getCourseName(int $courseId): ?string;

  public function enrollUser(int $vatsimId, int $courseId): bool;
}