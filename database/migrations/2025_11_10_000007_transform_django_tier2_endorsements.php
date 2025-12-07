<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('endorsements_tier2endorsement')) {
            return;
        }

        DB::statement('
            INSERT INTO tier2_endorsements (id, name, position, moodle_course_id, created_at, updated_at)
            SELECT 
                id,
                name,
                position,
                moodle_course_id,
                NOW() as created_at,
                NOW() as updated_at
            FROM endorsements_tier2endorsement
            WHERE NOT EXISTS (
                SELECT 1 FROM tier2_endorsements 
                WHERE tier2_endorsements.id = endorsements_tier2endorsement.id
            )
        ');
    }

    public function down(): void
    {
    }
};