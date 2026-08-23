<?php

namespace Database\Factories;

use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['black', 'white', 'red', 'blue', 'green', 'yellow', 'gray', 'navy'];

        return [
            'product_template_id' => ProductTemplate::factory(),
            'attributes' => [
                'size' => fake()->randomElement($sizes),
                'color' => fake()->randomElement($colors),
            ],
            'price_delta' => fake()->randomFloat(2, 0, 10),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
