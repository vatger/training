<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roster_rosterentry')) {
            return;
        }

        DB::statement('
            INSERT INTO roster_entries (id, user_id, last_session, removal_date, created_at, updated_at)
            SELECT 
                id,
                user_id,
                last_session,
                removal_date,
                NOW() as created_at,
                NOW() as updated_at
            FROM roster_rosterentry
            WHERE NOT EXISTS (
                SELECT 1 FROM roster_entries 
                WHERE roster_entries.user_id = roster_rosterentry.user_id
            )
        ');
    }

    public function down(): void
    {
    }
};