<?php

namespace App\Domain\Training\Actions;

use App\Domain\Training\Events\CourseFinished;
use App\Models\Course;
use App\Models\Familiarisation;
use App\Models\FamiliarisationSector;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinishCourse
{
    public function execute(Course $course, User $trainee, User $mentor): void
    {
        DB::transaction(function () use ($course, $trainee, $mentor) {
            DB::table('course_trainees')
                ->where('course_id', $course->id)
                ->where('user_id', $trainee->id)
                ->update(['completed_at' => now(), 'status' => 'completed']);

            $endorsementGroups = DB::table('course_endorsement_groups')
                ->where('course_id', $course->id)
                ->pluck('endorsement_group_name')
                ->toArray();

            if (!empty($endorsementGroups)) {
                $this->grantEndorsements($trainee, $endorsementGroups, $mentor);
            }

            if ($course->type === 'RTG' && $course->position === 'CTR') {
                $this->addFirFamiliarisations($trainee, $course, $mentor);
            } elseif ($course->type === 'FAM' && $course->familiarisation_sector_id) {
                $this->addSingleFamiliarisation($trainee, $course, $mentor);
            }
        });

        event(new CourseFinished($course, $trainee, $mentor));
    }

    private function grantEndorsements(User $trainee, array $endorsementGroups, User $mentor): void
    {
        try {
            $vatEudService = app(\App\Services\VatEudService::class);

            $existing = collect($vatEudService->getTier1Endorsements())
                ->where('user_cid', $trainee->vatsim_id)
                ->pluck('position')
                ->toArray();

            foreach ($endorsementGroups as $position) {
                if (in_array($position, $existing)) {
                    continue;
                }

                $result = $vatEudService->createTier1Endorsement($trainee->vatsim_id, $position, $mentor->vatsim_id);

                if ($result['success']) {
                    ActivityLogger::endorsementGranted($position, $trainee, $mentor, 'tier1');
                } else {
                    Log::warning('Failed to grant Tier 1 endorsement on course completion', [
                        'trainee_id' => $trainee->id,
                        'position'   => $position,
                        'error'      => $result['message'] ?? 'Unknown error',
                    ]);
                }
            }

            $vatEudService->refreshEndorsementCache();
        } catch (\Exception $e) {
            Log::error('Error granting endorsements on course finish', [
                'trainee_id'         => $trainee->id,
                'endorsement_groups' => $endorsementGroups,
                'error'              => $e->getMessage(),
            ]);
        }
    }

    private function addFirFamiliarisations(User $trainee, Course $course, User $mentor): void
    {
        if (!$course->mentor_group_id) {
            Log::warning('No mentor group for CTR course, cannot determine FIR', ['course_id' => $course->id]);
            return;
        }

        $mentorGroup = Role::find($course->mentor_group_id);
        if (!$mentorGroup) {
            Log::warning('Mentor group not found', ['mentor_group_id' => $course->mentor_group_id]);
            return;
        }

        $fir     = substr($mentorGroup->name, 0, 4);
        $sectors = FamiliarisationSector::where('fir', $fir)->get();

        foreach ($sectors as $sector) {
            if (Familiarisation::where('user_id', $trainee->id)->where('familiarisation_sector_id', $sector->id)->exists()) {
                continue;
            }

            Familiarisation::create([
                'user_id'                   => $trainee->id,
                'familiarisation_sector_id' => $sector->id,
            ]);

            ActivityLogger::familiarisationAdded($trainee, $sector->name, $sector->id, $fir, $mentor, $course, true);
        }
    }

    private function addSingleFamiliarisation(User $trainee, Course $course, User $mentor): void
    {
        $familiarisation = Familiarisation::firstOrCreate([
            'user_id'                   => $trainee->id,
            'familiarisation_sector_id' => $course->familiarisation_sector_id,
        ]);

        if ($familiarisation->wasRecentlyCreated) {
            $sector = FamiliarisationSector::find($course->familiarisation_sector_id);
            if ($sector) {
                ActivityLogger::familiarisationAdded($trainee, $sector->name, $sector->id, $sector->fir, $mentor, $course, true);
            }
        }
    }
}