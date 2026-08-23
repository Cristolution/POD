<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
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
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->e164PhoneNumber(),
            'role' => 'customer',
        ];
    }

    // ------------------------------------------------------------------
    // Role states
    // ------------------------------------------------------------------

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function designer(): static
    {
        return $this->state(fn () => ['role' => 'designer']);
    }

    public function printerProvider(): static
    {
        return $this->state(fn () => ['role' => 'printer_provider']);
    }

    public function customer(): static
    {
        return $this->state(fn () => ['role' => 'customer']);
    }
}
