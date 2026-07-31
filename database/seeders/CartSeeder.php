<?php

namespace Database\Seeders;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        // Half the customers have 1-3 cart items
        User::where('role', 'customer')
            ->inRandomOrder()
            ->take(5)
            ->each(function (User $user) {
                $mappings = DesignProductMapping::inRandomOrder()
                    ->take(fake()->numberBetween(1, 3))
                    ->get();

                foreach ($mappings as $mapping) {
                    // 60% chance of having a chosen variant
                    $variant = fake()->boolean(60)
                        ? ProductVariant::where('product_template_id', $mapping->product_template_id)
                            ->inRandomOrder()
                            ->first()
                        : null;

                    CartItem::factory()->create([
                        'user_id' => $user->id,
                        'design_product_mapping_id' => $mapping->id,
                        'product_variant_id' => $variant?->id,
                        'quantity' => fake()->numberBetween(1, 4),
                    ]);
                }
            });
    }
}
