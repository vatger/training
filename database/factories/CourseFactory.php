<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' Training',
            'trainee_display_name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'airport_name' => fake()->city(),
            'airport_icao' => 'ED' . fake()->randomLetter() . fake()->randomLetter(),
            'solo_station' => null,
            'mentor_group_id' => null,
            'min_rating' => 2,
            'max_rating' => 3,
            'type' => fake()->randomElement(['RTG', 'EDMT', 'GST', 'FAM', 'RST']),
            'position' => fake()->randomElement(['GND', 'TWR', 'APP', 'CTR']),
            'moodle_course_ids' => [],
            'familiarisation_sector_id' => null,
        ];
    }

    public function rtg(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'RTG',
        ]);
    }

    public function edmt(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'EDMT',
        ]);
    }

    public function gst(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'GST',
        ]);
    }

    public function tower(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'TWR',
        ]);
    }

    public function approach(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => 'APP',
        ]);
    }
}