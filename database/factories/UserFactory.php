<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vatsim_id' => fake()->unique()->numberBetween(1000000, 9999999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'subdivision' => fake()->randomElement(['GER', 'USA', 'GBR', 'FRA']),
            'rating' => fake()->numberBetween(1, 7),
            'last_rating_change' => now()->subDays(fake()->numberBetween(30, 365)),
            'is_staff' => false,
            'is_superuser' => false,
            'is_admin' => false,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is a VATSIM user (has vatsim_id).
     */
    public function vatsimUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'vatsim_id' => fake()->unique()->numberBetween(1000000, 9999999),
        ]);
    }

    /**
     * Indicate that the user is an admin (no vatsim_id).
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'vatsim_id' => fake()->unique()->numberBetween(9000000, 9999999),
            'is_admin' => true,
            'is_staff' => true,
            'is_superuser' => true,
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * Indicate that the user is staff.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_staff' => true,
        ]);
    }

    /**
     * Indicate that the user is a superuser.
     */
    public function superuser(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_superuser' => true,
            'is_staff' => true,
        ]);
    }

    /**
     * Indicate that the user is from Germany.
     */
    public function german(): static
    {
        return $this->state(fn (array $attributes) => [
            'subdivision' => 'GER',
        ]);
    }

    /**
     * Indicate that the user has a specific rating.
     */
    public function rating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $rating,
        ]);
    }
}