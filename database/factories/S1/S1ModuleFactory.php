<?php

namespace Database\Factories\S1;

use App\Models\S1\S1Module;
use Illuminate\Database\Eloquent\Factories\Factory;

class S1ModuleFactory extends Factory
{
    protected $model = S1Module::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'sequence_order' => $this->faker->numberBetween(1, 10),
            'description' => $this->faker->sentence(),
            'moodle_course_ids' => null,
            'moodle_quiz_ids' => null,
            'is_active' => true,
        ];
    }

    public function withMoodleCourses(array $courseIds): self
    {
        return $this->state(fn (array $attributes) => [
            'moodle_course_ids' => $courseIds,
        ]);
    }

    public function withMoodleQuizzes(array $quizIds): self
    {
        return $this->state(fn (array $attributes) => [
            'moodle_quiz_ids' => $quizIds,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}