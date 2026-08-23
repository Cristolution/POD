<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Cart;

use App\Models\DesignProductMapping;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_item_to_cart(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create(['final_price' => 25.00]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/cart/items', [
                'design_product_mapping_id' => $mapping->id,
                'quantity' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.design_product_mapping_id', $mapping->id)
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.unit_price', '25.00')
            ->assertJsonPath('data.line_total', '50.00');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'design_product_mapping_id' => $mapping->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_existing_line_increments_quantity(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create(['final_price' => 25.00]);

        $payload = [
            'design_product_mapping_id' => $mapping->id,
            'quantity' => 1,
        ];

        $this->actingAs($user, 'sanctum')->postJson('/api/me/cart/items', $payload)->assertCreated();
        $this->actingAs($user, 'sanctum')->postJson('/api/me/cart/items', $payload)->assertCreated();

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'design_product_mapping_id' => $mapping->id,
            'quantity' => 2,
        ]);
    }

    public function test_user_can_add_item_with_variant(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $template = ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create([
            'product_template_id' => $template->id,
            'final_price' => 25.00,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_template_id' => $template->id,
            'price_delta' => 5.00,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/cart/items', [
                'design_product_mapping_id' => $mapping->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('data.product_variant_id', $variant->id)
            ->assertJsonPath('data.unit_price', '30.00')
            ->assertJsonPath('data.line_total', '60.00');
    }

    public function test_quantity_validation(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $mapping = DesignProductMapping::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/cart/items', [
                'design_product_mapping_id' => $mapping->id,
                'quantity' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/cart/items', [
                'design_product_mapping_id' => $mapping->id,
                'quantity' => 101,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }

    public function test_anonymous_is_rejected(): void
    {
        $mapping = DesignProductMapping::factory()->create();

        $this->postJson('/api/me/cart/items', [
            'design_product_mapping_id' => $mapping->id,
            'quantity' => 1,
        ])->assertStatus(401);
    }
}
