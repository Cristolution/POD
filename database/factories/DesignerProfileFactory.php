<?php

namespace Database\Factories;

use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DesignerProfile>
 */
class DesignerProfileFactory extends Factory
{
    protected $model = DesignerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->designer(),
            'bio' => fake()->paragraph(),
            'is_verified' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['is_verified' => false]);
    }
}
