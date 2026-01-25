<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('s1_modules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->integer('sequence_order');
            $table->text('description')->nullable();
            $table->json('moodle_course_ids')->nullable();
            $table->json('moodle_quiz_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('sequence_order');
            $table->index('is_active');
        });

        Schema::create('s1_module_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->constrained('s1_modules')->onDelete('cascade');
            $table->timestamp('completed_at');
            $table->foreignId('completed_by_mentor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('was_reset')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'module_id']);
            $table->index(['user_id', 'completed_at']);
        });

        Schema::create('s1_waiting_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->constrained('s1_modules')->onDelete('cascade');
            $table->timestamp('joined_at');
            $table->timestamp('last_confirmed_at')->nullable();
            $table->timestamp('confirmation_due_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('activity_warning_sent_at')->nullable();
            $table->integer('confirmation_reminders_sent')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'module_id']);
            $table->index(['module_id', 'joined_at']);
            $table->index(['is_active', 'confirmation_due_at']);
        });

        Schema::create('s1_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('s1_modules')->onDelete('cascade');
            $table->foreignId('mentor_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('scheduled_at');
            $table->integer('max_trainees');
            $table->enum('language', ['DE', 'EN'])->default('DE');
            $table->boolean('signups_open')->default(true);
            $table->boolean('signups_locked')->default(false);
            $table->timestamp('signups_lock_at')->nullable();
            $table->boolean('attendance_completed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['module_id', 'scheduled_at']);
            $table->index(['scheduled_at', 'signups_locked']);
            $table->index('mentor_id');
        });

        Schema::create('s1_session_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('s1_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('waiting_list_id')->nullable()->constrained('s1_waiting_lists')->onDelete('set null');
            $table->timestamp('signed_up_at');
            $table->boolean('was_selected')->default(false);
            $table->timestamp('selected_at')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();

            $table->unique(['session_id', 'user_id']);
            $table->index(['session_id', 'was_selected']);
            $table->index('user_id');
        });

        Schema::create('s1_session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('s1_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('signup_id')->nullable()->constrained('s1_session_signups')->onDelete('set null');
            $table->enum('status', ['attended', 'absent', 'excused', 'passed', 'failed'])->default('attended');
            $table->text('notes')->nullable();
            $table->foreignId('marked_by_mentor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('marked_at')->nullable();
            $table->boolean('spontaneous')->default(false);
            $table->timestamps();

            $table->unique(['session_id', 'user_id']);
            $table->index(['session_id', 'status']);
            $table->index('user_id');
        });

        Schema::create('s1_user_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('reason');
            $table->timestamp('banned_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('banned_by_mentor_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['expires_at', 'is_active']);
        });

        Schema::create('s1_trainee_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->text('comment');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('author_id');
        });

        Schema::create('s1_progress_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('reset_by_mentor_id')->constrained('users')->onDelete('cascade');
            $table->text('reason');
            $table->json('modules_reset')->nullable();
            $table->json('moodle_data_backup')->nullable();
            $table->timestamp('reset_at');
            $table->timestamps();

            $table->index(['user_id', 'reset_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('s1_progress_resets');
        Schema::dropIfExists('s1_trainee_comments');
        Schema::dropIfExists('s1_user_bans');
        Schema::dropIfExists('s1_session_attendances');
        Schema::dropIfExists('s1_session_signups');
        Schema::dropIfExists('s1_sessions');
        Schema::dropIfExists('s1_waiting_lists');
        Schema::dropIfExists('s1_module_completions');
        Schema::dropIfExists('s1_modules');
    }
};