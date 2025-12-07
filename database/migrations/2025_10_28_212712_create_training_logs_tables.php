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
        Schema::create('training_logs', function (Blueprint $table) {
            $table->id();

            // Basic information
            $table->foreignId('trainee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('mentor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null');
            
            $table->date('session_date');
            $table->string('position', 25);
            $table->char('type', 1); // O = Online, S = Sim, L = Lesson, C = Custom

            // Session details (optional)
            $table->char('traffic_level', 1)->nullable(); // L = Low, M = Medium, H = High
            $table->char('traffic_complexity', 1)->nullable(); // L = Low, M = Medium, H = High
            $table->string('runway_configuration', 50)->nullable();
            $table->text('surrounding_stations')->nullable();
            $table->unsignedInteger('session_duration')->nullable(); // in minutes
            $table->text('special_procedures')->nullable();
            $table->text('airspace_restrictions')->nullable();

            // Evaluation categories
            // Theory
            $table->unsignedTinyInteger('theory'); // 0-4
            $table->text('theory_positives')->nullable();
            $table->text('theory_negatives')->nullable();
            
            // Phraseology
            $table->unsignedTinyInteger('phraseology'); // 0-4
            $table->text('phraseology_positives')->nullable();
            $table->text('phraseology_negatives')->nullable();
            
            // Coordination
            $table->unsignedTinyInteger('coordination'); // 0-4
            $table->text('coordination_positives')->nullable();
            $table->text('coordination_negatives')->nullable();
            
            // Tag Management
            $table->unsignedTinyInteger('tag_management'); // 0-4
            $table->text('tag_management_positives')->nullable();
            $table->text('tag_management_negatives')->nullable();
            
            // Situational Awareness
            $table->unsignedTinyInteger('situational_awareness'); // 0-4
            $table->text('situational_awareness_positives')->nullable();
            $table->text('situational_awareness_negatives')->nullable();
            
            // Problem Recognition
            $table->unsignedTinyInteger('problem_recognition'); // 0-4
            $table->text('problem_recognition_positives')->nullable();
            $table->text('problem_recognition_negatives')->nullable();
            
            // Traffic Planning
            $table->unsignedTinyInteger('traffic_planning'); // 0-4
            $table->text('traffic_planning_positives')->nullable();
            $table->text('traffic_planning_negatives')->nullable();
            
            // Reaction
            $table->unsignedTinyInteger('reaction'); // 0-4
            $table->text('reaction_positives')->nullable();
            $table->text('reaction_negatives')->nullable();
            
            // Separation
            $table->unsignedTinyInteger('separation'); // 0-4
            $table->text('separation_positives')->nullable();
            $table->text('separation_negatives')->nullable();
            
            // Efficiency
            $table->unsignedTinyInteger('efficiency'); // 0-4
            $table->text('efficiency_positives')->nullable();
            $table->text('efficiency_negatives')->nullable();
            
            // Ability to Work Under Pressure
            $table->unsignedTinyInteger('ability_to_work_under_pressure'); // 0-4
            $table->text('ability_to_work_under_pressure_positives')->nullable();
            $table->text('ability_to_work_under_pressure_negatives')->nullable();
            
            // Motivation
            $table->unsignedTinyInteger('motivation'); // 0-4
            $table->text('motivation_positives')->nullable();
            $table->text('motivation_negatives')->nullable();
            
            // Final assessment
            $table->text('internal_remarks')->nullable();
            $table->text('final_comment')->nullable();
            $table->boolean('result'); // Pass/Fail
            $table->text('next_step')->nullable();
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('trainee_id');
            $table->index('mentor_id');
            $table->index('course_id');
            $table->index('session_date');
            $table->index(['trainee_id', 'session_date']);
            $table->index(['course_id', 'session_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_logs');
    }
};