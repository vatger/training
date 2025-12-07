
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('familiarisations_familiarisationsector')) {
            return;
        }

        DB::statement('
            INSERT INTO familiarisation_sectors (id, name, fir, created_at, updated_at)
            SELECT 
                id,
                name,
                fir,
                NOW() as created_at,
                NOW() as updated_at
            FROM familiarisations_familiarisationsector
            WHERE NOT EXISTS (
                SELECT 1 FROM familiarisation_sectors 
                WHERE familiarisation_sectors.id = familiarisations_familiarisationsector.id
            )
        ');

        if (Schema::hasTable('familiarisations_familiarisation')) {
            DB::statement('
                INSERT INTO familiarisations (id, user_id, familiarisation_sector_id, created_at, updated_at)
                SELECT 
                    id,
                    user_id,
                    sector_id as familiarisation_sector_id,
                    NOW() as created_at,
                    NOW() as updated_at
                FROM familiarisations_familiarisation
                WHERE NOT EXISTS (
                    SELECT 1 FROM familiarisations 
                    WHERE familiarisations.id = familiarisations_familiarisation.id
                )
            ');
        }
    }

    public function down(): void
    {
    }
};