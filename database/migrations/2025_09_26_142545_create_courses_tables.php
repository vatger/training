<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familiarisation_sectors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 4);
            $table->enum('fir', ['EDGG', 'EDMM', 'EDWW']);
            $table->timestamps();
            
            $table->index(['fir', 'name']);
        });

        Schema::create('familiarisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('familiarisation_sector_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'familiarisation_sector_id'], 'user_fam_sector_unique');
            $table->index('user_id');
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('trainee_display_name', 100);
            $table->text('description')->nullable();
            $table->string('airport_name', 100);
            $table->string('airport_icao', 4);
            $table->string('solo_station', 15)->nullable();
            $table->foreignId('mentor_group_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->integer('min_rating');
            $table->integer('max_rating');
            $table->enum('type', ['EDMT', 'RTG', 'GST', 'FAM', 'RST']);
            $table->enum('position', ['GND', 'TWR', 'APP', 'CTR']);
            $table->json('moodle_course_ids');
            $table->foreignId('familiarisation_sector_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            
            $table->index(['type', 'position']);
            $table->index(['min_rating', 'max_rating']);
            $table->index('airport_icao');
        });

        Schema::create('course_endorsement_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('endorsement_group_name', 50);
            $table->timestamps();

            $table->unique(['course_id', 'endorsement_group_name'], 'course_endorse_group_unique');
        });

        Schema::create('course_mentors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['course_id', 'user_id'], 'course_mentor_unique');
        });

        Schema::create('course_trainees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('remark_author_id')->nullable();
            $table->timestamp('remark_updated_at')->nullable();
            $table->unsignedBigInteger('claimed_by_mentor_id')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('custom_order')->nullable();
            $table->unsignedBigInteger('custom_order_mentor_id')->nullable();
            $table->timestamps();

            $table->foreign('remark_author_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('claimed_by_mentor_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('custom_order_mentor_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->unique(['course_id', 'user_id'], 'course_trainee_unique');
            $table->index(['course_id', 'custom_order_mentor_id', 'custom_order'], 'course_trainee_order_idx');
        });

        Schema::create('waiting_list_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->timestamp('date_added')->useCurrent();
            $table->float('activity')->default(0);
            $table->dateTime('hours_updated')->default('2000-01-01 00:00:00');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id'], 'waiting_list_unique');
            $table->index(['course_id', 'date_added']);
            $table->index('activity');
        });

        Schema::create('roster_entries', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->dateTime('last_session')->default('1970-01-01 00:00:00');
            $table->dateTime('removal_date')->nullable();
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index(['last_session', 'removal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_entries');
        Schema::dropIfExists('waiting_list_entries');
        Schema::dropIfExists('course_trainees');
        Schema::dropIfExists('course_mentors');
        Schema::dropIfExists('course_endorsement_groups');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('familiarisations');
        Schema::dropIfExists('familiarisation_sectors');
    }
};