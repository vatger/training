<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingLog extends Model
{
    use HasFactory;

    /**
     * Session type constants
     */
    const TYPE_ONLINE = 'O';
    const TYPE_SIM = 'S';
    const TYPE_LESSON = 'L';
    const TYPE_CUSTOM = 'C';

    /**
     * Rating constants
     */
    const RATING_NOT_RATED = 0;
    const RATING_NOT_MET = 1;
    const RATING_PARTIALLY_MET = 2;
    const RATING_MET = 3;
    const RATING_EXCEEDED = 4;

    /**
     * Traffic level constants
     */
    const TRAFFIC_LOW = 'L';
    const TRAFFIC_MEDIUM = 'M';
    const TRAFFIC_HIGH = 'H';

    protected $fillable = [
        'trainee_id',
        'mentor_id',
        'course_id',
        'session_date',
        'position',
        'type',
        
        // Session details
        'traffic_level',
        'traffic_complexity',
        'runway_configuration',
        'surrounding_stations',
        'session_duration',
        'special_procedures',
        'airspace_restrictions',
        
        // Evaluation categories
        'theory',
        'theory_positives',
        'theory_negatives',
        
        'phraseology',
        'phraseology_positives',
        'phraseology_negatives',
        
        'coordination',
        'coordination_positives',
        'coordination_negatives',
        
        'tag_management',
        'tag_management_positives',
        'tag_management_negatives',
        
        'situational_awareness',
        'situational_awareness_positives',
        'situational_awareness_negatives',
        
        'problem_recognition',
        'problem_recognition_positives',
        'problem_recognition_negatives',
        
        'traffic_planning',
        'traffic_planning_positives',
        'traffic_planning_negatives',
        
        'reaction',
        'reaction_positives',
        'reaction_negatives',
        
        'separation',
        'separation_positives',
        'separation_negatives',
        
        'efficiency',
        'efficiency_positives',
        'efficiency_negatives',
        
        'ability_to_work_under_pressure',
        'ability_to_work_under_pressure_positives',
        'ability_to_work_under_pressure_negatives',
        
        'motivation',
        'motivation_positives',
        'motivation_negatives',
        
        // Final assessment
        'internal_remarks',
        'final_comment',
        'result',
        'next_step',
    ];

    protected $casts = [
        'session_date' => 'date',
        'result' => 'boolean',
        'session_duration' => 'integer',
        
        // Cast all rating fields to integers
        'theory' => 'integer',
        'phraseology' => 'integer',
        'coordination' => 'integer',
        'tag_management' => 'integer',
        'situational_awareness' => 'integer',
        'problem_recognition' => 'integer',
        'traffic_planning' => 'integer',
        'reaction' => 'integer',
        'separation' => 'integer',
        'efficiency' => 'integer',
        'ability_to_work_under_pressure' => 'integer',
        'motivation' => 'integer',
    ];

    /**
     * Get the trainee (user) who took this training session
     */
    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    /**
     * Get the mentor (user) who conducted this training session
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * Get the course this log belongs to
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get display name for session type
     */
    public function getTypeDisplayAttribute(): string
    {
        return match($this->type) {
            self::TYPE_ONLINE => 'Online',
            self::TYPE_SIM => 'Sim',
            self::TYPE_LESSON => 'Lesson',
            self::TYPE_CUSTOM => 'Custom',
            default => 'Unknown',
        };
    }

    /**
     * Get display name for a rating value
     */
    public static function getRatingDisplay(int $rating): string
    {
        return match($rating) {
            self::RATING_NOT_RATED => 'Not Rated',
            self::RATING_NOT_MET => 'Requirements Not Met',
            self::RATING_PARTIALLY_MET => 'Requirements Partially Met',
            self::RATING_MET => 'Requirements Met',
            self::RATING_EXCEEDED => 'Requirements Exceeded',
            default => 'Unknown',
        };
    }

    /**
     * Get display name for traffic level
     */
    public function getTrafficLevelDisplayAttribute(): ?string
    {
        if (!$this->traffic_level) {
            return null;
        }

        return match($this->traffic_level) {
            self::TRAFFIC_LOW => 'Low',
            self::TRAFFIC_MEDIUM => 'Medium',
            self::TRAFFIC_HIGH => 'High',
            default => null,
        };
    }

    /**
     * Get display name for traffic complexity
     */
    public function getTrafficComplexityDisplayAttribute(): ?string
    {
        if (!$this->traffic_complexity) {
            return null;
        }

        return match($this->traffic_complexity) {
            self::TRAFFIC_LOW => 'Low',
            self::TRAFFIC_MEDIUM => 'Medium',
            self::TRAFFIC_HIGH => 'High',
            default => null,
        };
    }

    /**
     * Get display for theory rating
     */
    public function getTheoryDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->theory);
    }

    /**
     * Get display for phraseology rating
     */
    public function getPhraseologyDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->phraseology);
    }

    /**
     * Get display for coordination rating
     */
    public function getCoordinationDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->coordination);
    }

    /**
     * Get display for tag management rating
     */
    public function getTagManagementDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->tag_management);
    }

    /**
     * Get display for situational awareness rating
     */
    public function getSituationalAwarenessDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->situational_awareness);
    }

    /**
     * Get display for problem recognition rating
     */
    public function getProblemRecognitionDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->problem_recognition);
    }

    /**
     * Get display for traffic planning rating
     */
    public function getTrafficPlanningDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->traffic_planning);
    }

    /**
     * Get display for reaction rating
     */
    public function getReactionDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->reaction);
    }

    /**
     * Get display for separation rating
     */
    public function getSeparationDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->separation);
    }

    /**
     * Get display for efficiency rating
     */
    public function getEfficiencyDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->efficiency);
    }

    /**
     * Get display for ability to work under pressure rating
     */
    public function getAbilityToWorkUnderPressureDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->ability_to_work_under_pressure);
    }

    /**
     * Get display for motivation rating
     */
    public function getMotivationDisplayAttribute(): string
    {
        return self::getRatingDisplay($this->motivation);
    }

    /**
     * Calculate average rating across all categories
     */
    public function getAverageRatingAttribute(): float
    {
        $categories = [
            'theory',
            'phraseology',
            'coordination',
            'tag_management',
            'situational_awareness',
            'problem_recognition',
            'traffic_planning',
            'reaction',
            'separation',
            'efficiency',
            'ability_to_work_under_pressure',
            'motivation',
        ];

        $ratedCategories = [];
        foreach ($categories as $category) {
            if ($this->$category > 0) {
                $ratedCategories[] = $this->$category;
            }
        }

        if (empty($ratedCategories)) {
            return 0.0;
        }

        return round(array_sum($ratedCategories) / count($ratedCategories), 2);
    }

    /**
     * Check if the log has any ratings
     */
    public function hasRatings(): bool
    {
        $categories = [
            'theory', 'phraseology', 'coordination', 'tag_management',
            'situational_awareness', 'problem_recognition', 'traffic_planning',
            'reaction', 'separation', 'efficiency',
            'ability_to_work_under_pressure', 'motivation',
        ];

        foreach ($categories as $category) {
            if ($this->$category > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope to get logs for a specific trainee
     */
    public function scopeForTrainee($query, int $traineeId)
    {
        return $query->where('trainee_id', $traineeId);
    }

    /**
     * Scope to get logs by a specific mentor
     */
    public function scopeByMentor($query, int $mentorId)
    {
        return $query->where('mentor_id', $mentorId);
    }

    /**
     * Scope to get logs for a specific course
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope to order by most recent first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('session_date', 'desc');
    }

    /**
     * Scope to get only passed sessions
     */
    public function scopePassed($query)
    {
        return $query->where('result', true);
    }

    /**
     * Scope to get only failed sessions
     */
    public function scopeFailed($query)
    {
        return $query->where('result', false);
    }

    /**
     * Get all evaluation categories as an array
     */
    public function getEvaluationCategories(): array
    {
        return [
            'theory' => [
                'rating' => $this->theory,
                'rating_display' => $this->theory_display,
                'positives' => $this->theory_positives,
                'negatives' => $this->theory_negatives,
            ],
            'phraseology' => [
                'rating' => $this->phraseology,
                'rating_display' => $this->phraseology_display,
                'positives' => $this->phraseology_positives,
                'negatives' => $this->phraseology_negatives,
            ],
            'coordination' => [
                'rating' => $this->coordination,
                'rating_display' => $this->coordination_display,
                'positives' => $this->coordination_positives,
                'negatives' => $this->coordination_negatives,
            ],
            'tag_management' => [
                'rating' => $this->tag_management,
                'rating_display' => $this->tag_management_display,
                'positives' => $this->tag_management_positives,
                'negatives' => $this->tag_management_negatives,
            ],
            'situational_awareness' => [
                'rating' => $this->situational_awareness,
                'rating_display' => $this->situational_awareness_display,
                'positives' => $this->situational_awareness_positives,
                'negatives' => $this->situational_awareness_negatives,
            ],
            'problem_recognition' => [
                'rating' => $this->problem_recognition,
                'rating_display' => $this->problem_recognition_display,
                'positives' => $this->problem_recognition_positives,
                'negatives' => $this->problem_recognition_negatives,
            ],
            'traffic_planning' => [
                'rating' => $this->traffic_planning,
                'rating_display' => $this->traffic_planning_display,
                'positives' => $this->traffic_planning_positives,
                'negatives' => $this->traffic_planning_negatives,
            ],
            'reaction' => [
                'rating' => $this->reaction,
                'rating_display' => $this->reaction_display,
                'positives' => $this->reaction_positives,
                'negatives' => $this->reaction_negatives,
            ],
            'separation' => [
                'rating' => $this->separation,
                'rating_display' => $this->separation_display,
                'positives' => $this->separation_positives,
                'negatives' => $this->separation_negatives,
            ],
            'efficiency' => [
                'rating' => $this->efficiency,
                'rating_display' => $this->efficiency_display,
                'positives' => $this->efficiency_positives,
                'negatives' => $this->efficiency_negatives,
            ],
            'ability_to_work_under_pressure' => [
                'rating' => $this->ability_to_work_under_pressure,
                'rating_display' => $this->ability_to_work_under_pressure_display,
                'positives' => $this->ability_to_work_under_pressure_positives,
                'negatives' => $this->ability_to_work_under_pressure_negatives,
            ],
            'motivation' => [
                'rating' => $this->motivation,
                'rating_display' => $this->motivation_display,
                'positives' => $this->motivation_positives,
                'negatives' => $this->motivation_negatives,
            ],
        ];
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return sprintf(
            'Training Log - %s - %s (%s)',
            $this->session_date->format('Y-m-d'),
            $this->trainee->name ?? 'Unknown',
            $this->position
        );
    }
}