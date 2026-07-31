<?php

namespace Database\Factories;

use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DesignerProfile>
 */
class DesignerProfileFactory extends Factory
{
    protected $model = DesignerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->designer(),
            'bio' => fake()->paragraph(),
        ];
    }
}
