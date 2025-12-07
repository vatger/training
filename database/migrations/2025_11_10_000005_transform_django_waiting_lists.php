<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lists_waitinglistentry')) {
            return;
        }

        DB::statement('
            INSERT INTO waiting_list_entries (
                id, user_id, course_id, date_added, activity, hours_updated, remarks, created_at, updated_at
            )
            SELECT 
                id,
                user_id,
                course_id,
                date_added,
                activity,
                hours_updated,
                remarks,
                date_added as created_at,
                date_added as updated_at
            FROM lists_waitinglistentry
            WHERE NOT EXISTS (
                SELECT 1 FROM waiting_list_entries 
                WHERE waiting_list_entries.id = lists_waitinglistentry.id
            )
        ');
    }

    public function down(): void
    {
    }
};