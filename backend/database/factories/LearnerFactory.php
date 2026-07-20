<?php

namespace Database\Factories;

use App\Models\Learner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Learner>
 */
class LearnerFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'display_name' => fake()->name(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'enrolled_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }
}
