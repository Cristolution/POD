<?php

namespace Database\Factories;

use App\Models\PrinterProviderProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrinterProviderProfile>
 */
class PrinterProviderProfileFactory extends Factory
{
    protected $model = PrinterProviderProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->printerProvider(),
            'company_name' => fake()->company(),
        ];
    }
}
