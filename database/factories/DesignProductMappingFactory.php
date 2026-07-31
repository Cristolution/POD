<?php

namespace Database\Factories;

use App\Models\Design;
use App\Models\DesignProductMapping;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DesignProductMapping>
 */
class DesignProductMappingFactory extends Factory
{
    protected $model = DesignProductMapping::class;

    public function definition(): array
    {
        return [
            'design_id' => Design::factory(),
            'product_template_id' => ProductTemplate::factory(),
            'preferred_printer_id' => PrinterProviderProfile::factory(),
            'final_price' => fake()->randomFloat(2, 10, 100),
        ];
    }
}
