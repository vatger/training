<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examiners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('callsign', 10)->unique();
            $table->json('positions')->default('[]');
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('cpts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('examiner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('local_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->dateTime('date');
            $table->boolean('passed')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->boolean('log_uploaded')->default(false);
            $table->timestamps();

            $table->index('trainee_id');
            $table->index('examiner_id');
            $table->index('local_id');
            $table->index('course_id');
            $table->index('date');
            $table->index(['passed', 'date']);
        });

        Schema::create('cpt_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cpt_id')->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by_id')->constrained('users')->onDelete('cascade');
            $table->string('log_file');
            $table->timestamps();

            $table->index('cpt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpt_logs');
        Schema::dropIfExists('cpts');
        Schema::dropIfExists('examiners');
    }
};