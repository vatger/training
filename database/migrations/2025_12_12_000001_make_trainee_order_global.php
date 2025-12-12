<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_trainees', function (Blueprint $table) {
            $table->dropForeign(['custom_order_mentor_id']);
            $table->dropColumn('custom_order_mentor_id');
            $table->dropIndex('course_trainee_order_idx');
        });

        Schema::table('course_trainees', function (Blueprint $table) {
            $table->index(['course_id', 'custom_order'], 'course_trainee_order_idx');
        });

        DB::statement('
            UPDATE course_trainees 
            SET custom_order = NULL 
            WHERE custom_order IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('course_trainees', function (Blueprint $table) {
            $table->dropIndex('course_trainee_order_idx');
        });

        Schema::table('course_trainees', function (Blueprint $table) {
            $table->unsignedBigInteger('custom_order_mentor_id')->nullable()->after('custom_order');
            
            $table->foreign('custom_order_mentor_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(['course_id', 'custom_order_mentor_id', 'custom_order'], 'course_trainee_order_idx');
        });
    }
};