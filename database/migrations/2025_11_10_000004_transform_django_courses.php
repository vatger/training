<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lists_course')) {
            return;
        }

        DB::statement("
            INSERT INTO courses (
                id, name, trainee_display_name, description, airport_name, airport_icao, 
                solo_station, mentor_group_id, min_rating, max_rating, type, position, 
                moodle_course_ids, familiarisation_sector_id, created_at, updated_at
            )
            SELECT 
                id,
                name,
                name as trainee_display_name,
                description,
                airport_name,
                airport_icao,
                solo_station,
                mentor_group_id,
                min_rating,
                max_rating,
                type,
                position,
                COALESCE(moodle_course_ids, '[]') as moodle_course_ids,
                familiarisation_sector_id,
                NOW() as created_at,
                NOW() as updated_at
            FROM lists_course
            WHERE NOT EXISTS (SELECT 1 FROM courses WHERE courses.id = lists_course.id)
        ");

        if (Schema::hasTable('lists_course_mentors')) {
            DB::statement('
                INSERT INTO course_mentors (course_id, user_id, created_at, updated_at)
                SELECT 
                    course_id,
                    user_id,
                    NOW() as created_at,
                    NOW() as updated_at
                FROM lists_course_mentors
                WHERE NOT EXISTS (
                    SELECT 1 FROM course_mentors 
                    WHERE course_mentors.course_id = lists_course_mentors.course_id 
                    AND course_mentors.user_id = lists_course_mentors.user_id
                )
            ');
        }

        if (Schema::hasTable('lists_course_active_trainees')) {
            DB::statement('
                INSERT INTO course_trainees (course_id, user_id, created_at, updated_at)
                SELECT 
                    course_id,
                    user_id,
                    NOW() as created_at,
                    NOW() as updated_at
                FROM lists_course_active_trainees
                WHERE NOT EXISTS (
                    SELECT 1 FROM course_trainees 
                    WHERE course_trainees.course_id = lists_course_active_trainees.course_id 
                    AND course_trainees.user_id = lists_course_active_trainees.user_id
                )
            ');
        }

        if (Schema::hasTable('lists_course_endorsement_groups') && Schema::hasTable('endorsements_endorsementgroup')) {
            DB::statement("
                INSERT INTO course_endorsement_groups (course_id, endorsement_group_name, created_at, updated_at)
                SELECT 
                    lceg.course_id,
                    CONVERT(eg.name USING utf8mb4) COLLATE utf8mb4_unicode_ci as endorsement_group_name,
                    NOW() as created_at,
                    NOW() as updated_at
                FROM lists_course_endorsement_groups lceg
                JOIN endorsements_endorsementgroup eg ON lceg.endorsementgroup_id = eg.id
                WHERE NOT EXISTS (
                    SELECT 1 FROM course_endorsement_groups ceg
                    WHERE ceg.course_id = lceg.course_id 
                    AND ceg.endorsement_group_name = CONVERT(eg.name USING utf8mb4) COLLATE utf8mb4_unicode_ci
                )
            ");
        }

        if (Schema::hasTable('overview_traineeremark')) {
            DB::statement('
                UPDATE course_trainees ct
                JOIN overview_traineeremark tr ON ct.course_id = tr.course_id AND ct.user_id = tr.trainee_id
                SET 
                    ct.remarks = tr.remark,
                    ct.remark_author_id = tr.last_updated_by_id,
                    ct.remark_updated_at = tr.last_updated
                WHERE ct.remarks IS NULL
            ');
        }

        if (Schema::hasTable('overview_traineeclaim')) {
            DB::statement('
                UPDATE course_trainees ct
                JOIN overview_traineeclaim tc ON ct.course_id = tc.course_id AND ct.user_id = tc.trainee_id
                SET 
                    ct.claimed_by_mentor_id = tc.mentor_id,
                    ct.claimed_at = NOW()
                WHERE ct.claimed_by_mentor_id IS NULL
            ');
        }
    }

    public function down(): void
    {
    }
};