<?php

namespace Database\Factories\S1;

use App\Models\S1\S1Session;
use App\Models\S1\S1Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class S1SessionFactory extends Factory
{
    protected $model = S1Session::class;

    public function definition(): array
    {
        return [
            'module_id' => S1Module::factory(),
            'mentor_id' => User::factory(),
            'scheduled_at' => Carbon::now()->addDays(7),
            'max_trainees' => 15,
            'language' => 'DE',
            'signups_open' => true,
            'signups_locked' => false,
            'signups_lock_at' => Carbon::now()->addDays(5),
            'attendance_completed' => false,
            'notes' => null,
        ];
    }

    public function locked(): self
    {
        return $this->state(fn (array $attributes) => [
            'signups_locked' => true,
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'attendance_completed' => true,
        ]);
    }

    public function english(): self
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'EN',
        ]);
    }
}