<?php

namespace Database\Factories;

use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductTemplate>
 */
class ProductTemplateFactory extends Factory
{
    protected $model = ProductTemplate::class;

    public function definition(): array
    {
        // Product types derived from the user-made mockup assets at
        // C:/Users/Crist/Desktop/enhanced mockup data assets/pod{N}/.
        // Each pod{N}_<suffix>.jpg exists, so each suffix becomes a type.
        // No mouse pad / laptop sleeves / greeting cards — those aren't in the assets.
        $types = [
            'cap',
            'hoodie',
            'mug',
            'tote bag',
            't-shirt',
            'canvas',
            'sticker',
            'poster',
            'long sleeve',
            'bottle',
            'phone case',
            'notebook',
            'thermos',
        ];

        return [
            'printer_provider_id' => PrinterProviderProfile::factory(),
            'type' => fake()->randomElement($types),
            'base_cost' => fake()->randomFloat(2, 5, 50),
            'specs' => [
                'material' => fake()->randomElement(['cotton', 'ceramic', 'paper', 'polyester']),
                'print_area' => fake()->randomElement(['30x40cm', '20x30cm', 'A4', 'A3']),
                'print_method' => fake()->randomElement(['DTG', 'screen', 'sublimation', 'UV']),
            ],
        ];
    }
}
