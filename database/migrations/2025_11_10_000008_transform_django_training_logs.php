<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('logs_log')) {
            return;
        }

        DB::statement("
            INSERT INTO training_logs (
                id, trainee_id, mentor_id, course_id, session_date, position, type,
                traffic_level, traffic_complexity, runway_configuration, surrounding_stations,
                session_duration, special_procedures, airspace_restrictions,
                theory, theory_positives, theory_negatives,
                phraseology, phraseology_positives, phraseology_negatives,
                coordination, coordination_positives, coordination_negatives,
                tag_management, tag_management_positives, tag_management_negatives,
                situational_awareness, situational_awareness_positives, situational_awareness_negatives,
                problem_recognition, problem_recognition_positives, problem_recognition_negatives,
                traffic_planning, traffic_planning_positives, traffic_planning_negatives,
                reaction, reaction_positives, reaction_negatives,
                separation, separation_positives, separation_negatives,
                efficiency, efficiency_positives, efficiency_negatives,
                ability_to_work_under_pressure, ability_to_work_under_pressure_positives, ability_to_work_under_pressure_negatives,
                motivation, motivation_positives, motivation_negatives,
                internal_remarks, final_comment, result, next_step,
                created_at, updated_at
            )
            SELECT 
                id,
                trainee_id,
                mentor_id,
                course_id,
                session_date,
                position,
                type,
                traffic_level,
                traffic_complexity,
                runway_configuration,
                surrounding_stations,
                session_duration,
                special_procedures,
                airspace_restrictions,
                theory,
                theory_positives,
                theory_negatives,
                phraseology,
                phraseology_positives,
                phraseology_negatives,
                coordination,
                coordination_positives,
                coordination_negatives,
                tag_management,
                tag_management_positives,
                tag_management_negatives,
                situational_awareness,
                situational_awareness_positives,
                situational_awareness_negatives,
                problem_recognition,
                problem_recognition_positives,
                problem_recognition_negatives,
                traffic_planning,
                traffic_planning_positives,
                traffic_planning_negatives,
                reaction,
                reaction_positives,
                reaction_negatives,
                separation,
                separation_positives,
                separation_negatives,
                efficiency,
                efficiency_positives,
                efficiency_negatives,
                ability_to_work_under_pressure,
                ability_to_work_under_pressure_positives,
                ability_to_work_under_pressure_negatives,
                motivation,
                motivation_positives,
                motivation_negatives,
                internal_remarks,
                final_comment,
                result,
                next_step,
                NOW() as created_at,
                NOW() as updated_at
            FROM logs_log
            WHERE NOT EXISTS (SELECT 1 FROM training_logs WHERE training_logs.id = logs_log.id)
        ");
    }

    public function down(): void
    {
    }
};