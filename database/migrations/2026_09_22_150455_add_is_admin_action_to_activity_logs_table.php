<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Marks log entries that record a change made directly through
            // the admin panel (as opposed to the pre-existing domain-event
            // log entries, e.g. solo granted, CPT graded). These are only
            // visible to admins — superusers can see everything else.
            $table->boolean('is_admin_action')->default(false)->after('description')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('is_admin_action');
        });
    }
};
