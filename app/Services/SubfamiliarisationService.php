<?php

namespace App\Services;

use App\Integrations\Moodle\MoodleClientInterface;
use App\Models\Course;
use App\Models\Familiarisation;
use App\Models\Subfamiliarisation;
use App\Models\User;
use Illuminate\Support\Collection;

class SubfamiliarisationService
{
    public function __construct(
        private readonly MoodleClientInterface $moodle,
    ) {}

    public function heldSectorIds(User $user, Subfamiliarisation $subfamiliarisation): array
    {
        return Familiarisation::where('user_id', $user->id)
            ->whereIn('familiarisation_sector_id', $subfamiliarisation->sectorIds())
            ->pluck('familiarisation_sector_id')
            ->all();
    }

    public function hasCompletedMoodleRequirements(User $user, Subfamiliarisation $subfamiliarisation): bool
    {
        foreach ($subfamiliarisation->moodle_course_ids as $moodleCourseId) {
            if (! $this->moodle->getCourseCompletion($user->vatsim_id, (int) $moodleCourseId)) {
                return false;
            }
        }

        return true;
    }

    public function hasCompletedPracticalSession(User $user, Subfamiliarisation $subfamiliarisation): bool
    {
        return $subfamiliarisation->sessions()->where('user_id', $user->id)->exists();
    }

    public function qualifiesViaSubfamiliarisation(User $user, Subfamiliarisation $subfamiliarisation): bool
    {
        if (count($this->heldSectorIds($user, $subfamiliarisation)) !== 1) {
            return false;
        }

        return $this->hasCompletedMoodleRequirements($user, $subfamiliarisation)
            && $this->hasCompletedPracticalSession($user, $subfamiliarisation);
    }

    public function isQualifiedForCombinedControl(User $user, Subfamiliarisation $subfamiliarisation): bool
    {
        if (count($this->heldSectorIds($user, $subfamiliarisation)) === 2) {
            return true;
        }

        return $this->qualifiesViaSubfamiliarisation($user, $subfamiliarisation);
    }

    public function grantedSectorId(User $user, Subfamiliarisation $subfamiliarisation): ?int
    {
        $held = $this->heldSectorIds($user, $subfamiliarisation);

        if (count($held) !== 1) {
            return null;
        }

        return collect($subfamiliarisation->sectorIds())->first(fn ($id) => ! in_array($id, $held, true));
    }

    public function canLogSession(User $mentor, Subfamiliarisation $subfamiliarisation): bool
    {
        if ($mentor->is_superuser || $mentor->is_admin) {
            return true;
        }

        $sectorIds = $subfamiliarisation->sectorIds();

        return $mentor->mentorCourses()
            ->where(function ($query) use ($sectorIds) {
                $query->whereIn('courses.familiarisation_sector_id', $sectorIds)
                    ->orWhereHas(
                        'requiredFamiliarisationSectors',
                        fn ($q) => $q->whereIn('familiarisation_sectors.id', $sectorIds)
                    );
            })
            ->exists();
    }

    public function relevantCourses(Subfamiliarisation $subfamiliarisation): Collection
    {
        $sectorIds = $subfamiliarisation->sectorIds();

        return Course::query()
            ->whereIn('familiarisation_sector_id', $sectorIds)
            ->orWhereHas(
                'requiredFamiliarisationSectors',
                fn ($q) => $q->whereIn('familiarisation_sectors.id', $sectorIds)
            )
            ->get();
    }

    public function relevantSubfamiliarisationsForCourse(Course $course): Collection
    {
        $requiredSectorIds = $course->requiredFamiliarisationSectors()->pluck('familiarisation_sectors.id');

        return Subfamiliarisation::with(['primarySector', 'secondarySector'])
            ->get()
            ->filter(function (Subfamiliarisation $subfamiliarisation) use ($course, $requiredSectorIds) {
                $sectorIds = $subfamiliarisation->sectorIds();

                return in_array($course->familiarisation_sector_id, $sectorIds, true)
                    || $requiredSectorIds->intersect($sectorIds)->isNotEmpty();
            })
            ->values();
    }
}
