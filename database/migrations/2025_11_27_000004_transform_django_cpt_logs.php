<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cpt_cptlog')) {
            return;
        }

        DB::statement('
            INSERT INTO cpt_logs (id, cpt_id, uploaded_by_id, log_file, created_at, updated_at)
            SELECT 
                id,
                cpt_id,
                uploaded_by_id,
                log_file,
                uploaded_at as created_at,
                uploaded_at as updated_at
            FROM cpt_cptlog
            WHERE NOT EXISTS (
                SELECT 1 FROM cpt_logs 
                WHERE cpt_logs.id = cpt_cptlog.id
            )
        ');
    }

    public function down(): void
    {
    }
};