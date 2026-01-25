<?php

namespace Database\Factories\S1;

use App\Models\S1\S1ModuleCompletion;
use App\Models\S1\S1Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class S1ModuleCompletionFactory extends Factory
{
    protected $model = S1ModuleCompletion::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'module_id' => S1Module::factory(),
            'completed_at' => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
            'completed_by_mentor_id' => User::factory(),
            'was_reset' => false,
        ];
    }

    public function reset(): self
    {
        return $this->state(fn (array $attributes) => [
            'was_reset' => true,
        ]);
    }

    public function automatic(): self
    {
        return $this->state(fn (array $attributes) => [
            'completed_by_mentor_id' => null,
        ]);
    }
}