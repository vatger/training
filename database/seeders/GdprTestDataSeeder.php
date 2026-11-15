<?php

namespace Database\Seeders;

use App\Domain\Gdpr\UserDataMap;
use App\Models\Course;
use App\Models\GdprRemovalRequest;
use App\Models\Role;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Local test data for the GDPR removal flow:
 *
 *   php artisan db:seed --class=GdprTestDataSeeder
 *
 * Creates a VATGER Leitung user (9000000) to review with, two pending removal requests
 * (the trainee 9000001 and the mentor 9000002) and a bystander trainee (9000003) whose
 * records mention both of them. Safe to run repeatedly.
 */
class GdprTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $leitung = $this->user(9000000, 'Lena', 'Leitung');
        $leitung->roles()->syncWithoutDetaching([Role::firstOrCreate(['name' => 'VATGER Leitung'])->id]);

        $trainee = $this->user(9000001, 'Erika', 'Musterfrau', ['email' => 'erika.musterfrau@example.com']);
        $mentor = $this->user(9000002, 'Max', 'Mentor', ['email' => 'max.mentor@example.com']);
        $bystander = $this->user(9000003, 'Tim', 'Trainee');

        $course = Course::first() ?? Course::factory()->create();
        $mentorRole = Role::firstOrCreate(['name' => 'EDGG Mentor']);
        $mentor->roles()->syncWithoutDetaching([$mentorRole->id]);
        DB::table('course_mentors')->insertOrIgnore(['course_id' => $course->id, 'user_id' => $mentor->id, 'created_at' => now(), 'updated_at' => now()]);

        // Erika (trainee): her own data, all of which is deleted with her.
        WaitingListEntry::firstOrCreate(['user_id' => $trainee->id, 'course_id' => $course->id], ['remarks' => 'Erika Musterfrau möchte abends fliegen']);
        DB::table('course_trainees')->insertOrIgnore([
            'course_id' => $course->id, 'user_id' => $trainee->id, 'status' => 'active',
            'remarks' => 'Erika ist sehr motiviert', 'remark_author_id' => $mentor->id,
            'claimed_by_mentor_id' => $mentor->id, 'claimed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('user_settings')->insertOrIgnore(['user_id' => $trainee->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('roster_entries')->insertOrIgnore(['user_id' => 9000001, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('cpts')->insertOrIgnore([
            'id' => 900001, 'trainee_id' => $trainee->id, 'examiner_id' => $mentor->id, 'course_id' => $course->id,
            'date' => now()->subWeek(), 'passed' => true, 'confirmed' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (! TrainingLog::where('trainee_id', $trainee->id)->exists()) {
            foreach (range(1, 3) as $i) {
                $this->log(['trainee_id' => $trainee->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id, 'session_date' => now()->subDays($i * 7)->toDateString(), 'final_comment' => "Session {$i} mit Erika Musterfrau"]);
            }
        }

        // Max (mentor): logs he wrote for the bystander stay, and are reviewed after approval.
        if (! TrainingLog::where('trainee_id', $bystander->id)->exists()) {
            $this->log(['trainee_id' => $bystander->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id, 'final_comment' => 'Gute Session, Gruß Max Mentor (CID 9000002)', 'next_step' => 'Nächste Session mit Erika Musterfrau zusammen']);
            $this->log(['trainee_id' => $bystander->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id, 'final_comment' => 'Ohne persönliche Angaben']);
            $this->log(['trainee_id' => $bystander->id, 'mentor_id' => $leitung->id, 'course_id' => $course->id, 'internal_remarks' => 'Rücksprache mit Max Mentor gehalten']);
        }

        foreach ([$trainee, $mentor] as $user) {
            GdprRemovalRequest::firstOrCreate(
                ['vatsim_id' => $user->vatsim_id, 'status' => GdprRemovalRequest::STATUS_PENDING],
                ['user_id' => $user->id, 'user_name' => $user->name, 'search_terms' => UserDataMap::searchTerms($user)],
            );
        }
    }

    private function user(int $cid, string $first, string $last, array $extra = []): User
    {
        return User::firstOrCreate(['vatsim_id' => $cid], $extra + [
            'first_name' => $first, 'last_name' => $last, 'subdivision' => 'GER', 'rating' => 2, 'password' => 'password',
        ]);
    }

    private function log(array $attributes): TrainingLog
    {
        return TrainingLog::create($attributes + [
            'session_date' => now()->toDateString(), 'position' => 'EDDF_TWR', 'type' => 'O',
            'theory' => 3, 'phraseology' => 3, 'coordination' => 3, 'tag_management' => 3, 'situational_awareness' => 3,
            'problem_recognition' => 3, 'traffic_planning' => 3, 'reaction' => 3, 'separation' => 3, 'efficiency' => 3,
            'ability_to_work_under_pressure' => 3, 'motivation' => 3, 'result' => true,
        ]);
    }
}
