<?php

namespace Database\Factories\S1;

use App\Models\S1\S1SessionSignup;
use App\Models\S1\S1Session;
use App\Models\S1\S1WaitingList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class S1SessionSignupFactory extends Factory
{
    protected $model = S1SessionSignup::class;

    public function definition(): array
    {
        return [
            'session_id' => S1Session::factory(),
            'user_id' => User::factory(),
            'waiting_list_id' => S1WaitingList::factory(),
            'signed_up_at' => Carbon::now(),
            'was_selected' => false,
            'selected_at' => null,
            'notification_sent' => false,
        ];
    }

    public function selected(): self
    {
        return $this->state(fn (array $attributes) => [
            'was_selected' => true,
            'selected_at' => Carbon::now(),
        ]);
    }

    public function notified(): self
    {
        return $this->state(fn (array $attributes) => [
            'notification_sent' => true,
        ]);
    }
}