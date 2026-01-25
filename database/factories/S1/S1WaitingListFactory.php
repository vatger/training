<?php

namespace Database\Factories\S1;

use App\Models\S1\S1WaitingList;
use App\Models\S1\S1Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class S1WaitingListFactory extends Factory
{
    protected $model = S1WaitingList::class;

    public function definition(): array
    {
        $joinedAt = Carbon::now()->subDays($this->faker->numberBetween(1, 30));
        
        return [
            'user_id' => User::factory(),
            'module_id' => S1Module::factory(),
            'joined_at' => $joinedAt,
            'last_confirmed_at' => $joinedAt,
            'confirmation_due_at' => $joinedAt->copy()->addDays(30),
            'expires_at' => $joinedAt->copy()->addDays(63)->startOfMonth(),
            'is_active' => true,
            'activity_warning_sent_at' => null,
            'confirmation_reminders_sent' => 0,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function needingConfirmation(): self
    {
        return $this->state(fn (array $attributes) => [
            'confirmation_due_at' => Carbon::now()->subDay(),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->subDay(),
        ]);
    }
}