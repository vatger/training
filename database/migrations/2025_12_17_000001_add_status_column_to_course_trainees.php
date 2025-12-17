<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_trainees', function (Blueprint $table) {
            $table->string('status')->default('active')->after('completed_at');
        });

        DB::table('course_trainees')
            ->whereNotNull('completed_at')
            ->update(['status' => 'completed']);
    }

    public function down(): void
    {
        Schema::table('course_trainees', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};