<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainingLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        
        // Must be a mentor or superuser
        if (!$user || (!$user->isMentor() && !$user->is_superuser)) {
            return false;
        }

        // If updating, check if user is the mentor who created the log
        if ($this->route('training_log')) {
            $log = $this->route('training_log');
            return $user->id === $log->mentor_id || $user->is_superuser;
        }

        // If creating, check if user is a mentor for the course
        if ($this->has('course_id')) {
            $courseId = $this->input('course_id');
            return $user->is_superuser 
                || $user->is_admin 
                || $user->mentorCourses()->where('courses.id', $courseId)->exists();
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'session_date' => 'required|date|before_or_equal:today',
            'position' => 'required|string|max:25',
            'type' => 'required|string|in:O,S,L,C',
            
            // Session details (optional)
            'traffic_level' => 'nullable|string|in:L,M,H',
            'traffic_complexity' => 'nullable|string|in:L,M,H',
            'runway_configuration' => 'nullable|string|max:50',
            'surrounding_stations' => 'nullable|string|max:500',
            'session_duration' => 'nullable|integer|min:1|max:480',
            'special_procedures' => 'nullable|string|max:1000',
            'airspace_restrictions' => 'nullable|string|max:1000',
            
            // Rating categories - all required
            'theory' => 'required|integer|min:0|max:4',
            'theory_positives' => 'nullable|string|max:2000',
            'theory_negatives' => 'nullable|string|max:2000',
            
            'phraseology' => 'required|integer|min:0|max:4',
            'phraseology_positives' => 'nullable|string|max:2000',
            'phraseology_negatives' => 'nullable|string|max:2000',
            
            'coordination' => 'required|integer|min:0|max:4',
            'coordination_positives' => 'nullable|string|max:2000',
            'coordination_negatives' => 'nullable|string|max:2000',
            
            'tag_management' => 'required|integer|min:0|max:4',
            'tag_management_positives' => 'nullable|string|max:2000',
            'tag_management_negatives' => 'nullable|string|max:2000',
            
            'situational_awareness' => 'required|integer|min:0|max:4',
            'situational_awareness_positives' => 'nullable|string|max:2000',
            'situational_awareness_negatives' => 'nullable|string|max:2000',
            
            'problem_recognition' => 'required|integer|min:0|max:4',
            'problem_recognition_positives' => 'nullable|string|max:2000',
            'problem_recognition_negatives' => 'nullable|string|max:2000',
            
            'traffic_planning' => 'required|integer|min:0|max:4',
            'traffic_planning_positives' => 'nullable|string|max:2000',
            'traffic_planning_negatives' => 'nullable|string|max:2000',
            
            'reaction' => 'required|integer|min:0|max:4',
            'reaction_positives' => 'nullable|string|max:2000',
            'reaction_negatives' => 'nullable|string|max:2000',
            
            'separation' => 'required|integer|min:0|max:4',
            'separation_positives' => 'nullable|string|max:2000',
            'separation_negatives' => 'nullable|string|max:2000',
            
            'efficiency' => 'required|integer|min:0|max:4',
            'efficiency_positives' => 'nullable|string|max:2000',
            'efficiency_negatives' => 'nullable|string|max:2000',
            
            'ability_to_work_under_pressure' => 'required|integer|min:0|max:4',
            'ability_to_work_under_pressure_positives' => 'nullable|string|max:2000',
            'ability_to_work_under_pressure_negatives' => 'nullable|string|max:2000',
            
            'motivation' => 'required|integer|min:0|max:4',
            'motivation_positives' => 'nullable|string|max:2000',
            'motivation_negatives' => 'nullable|string|max:2000',
            
            // Final assessment
            'internal_remarks' => 'nullable|string|max:3000',
            'final_comment' => 'nullable|string|max:3000',
            'result' => 'required|boolean',
            'next_step' => 'nullable|string|max:500',
        ];

        // Add trainee and course validation only for store requests
        if ($this->isMethod('post')) {
            $rules['trainee_id'] = 'required|integer|exists:users,id';
            $rules['course_id'] = 'required|integer|exists:courses,id';
        }

        return $rules;
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'session_date' => 'session date',
            'position' => 'position',
            'type' => 'session type',
            'traffic_level' => 'traffic level',
            'traffic_complexity' => 'traffic complexity',
            'runway_configuration' => 'runway configuration',
            'surrounding_stations' => 'surrounding stations',
            'session_duration' => 'session duration',
            'special_procedures' => 'special procedures',
            'airspace_restrictions' => 'airspace restrictions',
            
            'theory' => 'theory rating',
            'theory_positives' => 'theory positive feedback',
            'theory_negatives' => 'theory areas for improvement',
            
            'phraseology' => 'phraseology rating',
            'phraseology_positives' => 'phraseology positive feedback',
            'phraseology_negatives' => 'phraseology areas for improvement',
            
            'coordination' => 'coordination rating',
            'coordination_positives' => 'coordination positive feedback',
            'coordination_negatives' => 'coordination areas for improvement',
            
            'tag_management' => 'tag management rating',
            'tag_management_positives' => 'tag management positive feedback',
            'tag_management_negatives' => 'tag management areas for improvement',
            
            'situational_awareness' => 'situational awareness rating',
            'situational_awareness_positives' => 'situational awareness positive feedback',
            'situational_awareness_negatives' => 'situational awareness areas for improvement',
            
            'problem_recognition' => 'problem recognition rating',
            'problem_recognition_positives' => 'problem recognition positive feedback',
            'problem_recognition_negatives' => 'problem recognition areas for improvement',
            
            'traffic_planning' => 'traffic planning rating',
            'traffic_planning_positives' => 'traffic planning positive feedback',
            'traffic_planning_negatives' => 'traffic planning areas for improvement',
            
            'reaction' => 'reaction rating',
            'reaction_positives' => 'reaction positive feedback',
            'reaction_negatives' => 'reaction areas for improvement',
            
            'separation' => 'separation rating',
            'separation_positives' => 'separation positive feedback',
            'separation_negatives' => 'separation areas for improvement',
            
            'efficiency' => 'efficiency rating',
            'efficiency_positives' => 'efficiency positive feedback',
            'efficiency_negatives' => 'efficiency areas for improvement',
            
            'ability_to_work_under_pressure' => 'ability to work under pressure rating',
            'ability_to_work_under_pressure_positives' => 'ability to work under pressure positive feedback',
            'ability_to_work_under_pressure_negatives' => 'ability to work under pressure areas for improvement',
            
            'motivation' => 'motivation rating',
            'motivation_positives' => 'motivation positive feedback',
            'motivation_negatives' => 'motivation areas for improvement',
            
            'internal_remarks' => 'internal remarks',
            'final_comment' => 'final comment',
            'result' => 'session result',
            'next_step' => 'next step',
            
            'trainee_id' => 'trainee',
            'course_id' => 'course',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'session_date.before_or_equal' => 'The session date cannot be in the future.',
            'session_duration.max' => 'Session duration cannot exceed 8 hours (480 minutes).',
            '*.max' => 'The :attribute is too long.',
            '*.required' => 'The :attribute is required.',
            'type.in' => 'Invalid session type selected.',
            'traffic_level.in' => 'Invalid traffic level selected.',
            'traffic_complexity.in' => 'Invalid traffic complexity selected.',
        ];
    }
}