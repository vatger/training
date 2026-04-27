<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endorsement_activities', function (Blueprint $table) {
            $table->date('low_activity_since')->nullable()->after('last_activity_date');
        });
    }

    public function down(): void
    {
        Schema::table('endorsement_activities', function (Blueprint $table) {
            $table->dropColumn('low_activity_since');
        });
    }
};