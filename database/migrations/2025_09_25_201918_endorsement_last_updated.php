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
        Schema::table('endorsement_activities', function (Blueprint $table) {
            $table->date('last_activity_date')->nullable()->after('activity_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('endorsement_activities', function (Blueprint $table) {
            $table->dropColumn('last_activity_date');
        });
    }
};