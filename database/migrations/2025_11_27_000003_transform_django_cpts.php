<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cpt_cpt')) {
            return;
        }

        DB::statement('
            INSERT INTO cpts (
                id, trainee_id, examiner_id, local_id, course_id, 
                date, passed, confirmed, log_uploaded, created_at, updated_at
            )
            SELECT 
                id,
                trainee_id,
                examiner_id,
                local_id,
                course_id,
                date,
                passed,
                confirmed,
                log_uploaded,
                NOW() as created_at,
                NOW() as updated_at
            FROM cpt_cpt
            WHERE NOT EXISTS (
                SELECT 1 FROM cpts 
                WHERE cpts.id = cpt_cpt.id
            )
        ');
    }

    public function down(): void
    {
    }
};