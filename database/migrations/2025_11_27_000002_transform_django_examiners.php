<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cpt_examiner')) {
            return;
        }

        DB::statement('
            INSERT INTO examiners (id, user_id, callsign, positions, created_at, updated_at)
            SELECT 
                id,
                user_id,
                callsign,
                JSON_ARRAY() as positions,
                NOW() as created_at,
                NOW() as updated_at
            FROM cpt_examiner
            WHERE NOT EXISTS (
                SELECT 1 FROM examiners 
                WHERE examiners.user_id = cpt_examiner.user_id
            )
        ');

        if (Schema::hasTable('cpt_examiner_positions') && Schema::hasTable('cpt_examinerposition')) {
            DB::statement("
                UPDATE examiners e
                SET positions = (
                    SELECT JSON_ARRAYAGG(ep.position)
                    FROM cpt_examiner_positions cep
                    JOIN cpt_examinerposition ep ON cep.examinerposition_id = ep.id
                    WHERE cep.examiner_id = e.id
                )
                WHERE EXISTS (
                    SELECT 1 
                    FROM cpt_examiner_positions cep
                    WHERE cep.examiner_id = e.id
                )
            ");
        }
    }

    public function down(): void
    {
    }
};