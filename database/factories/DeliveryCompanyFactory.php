<?php

namespace Database\Factories;

use App\Models\DeliveryCompany;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryCompany>
 */
class DeliveryCompanyFactory extends Factory
{
    protected $model = DeliveryCompany::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'coverage_zones' => ['US', 'EU', 'CA'],
            'tracking_url_pattern' => 'https://track.' . Str::slug($name) . '.com/{number}',
        ];
    }
}
