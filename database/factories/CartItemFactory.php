<?php

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'design_product_mapping_id' => DesignProductMapping::factory(),
            'product_variant_id' => null,
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
