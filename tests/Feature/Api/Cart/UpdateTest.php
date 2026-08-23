<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Cart;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_quantity(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create(['final_price' => 25.00]);
        $item = CartItem::factory()->create([
            'user_id' => $user->id,
            'design_product_mapping_id' => $mapping->id,
            'product_variant_id' => null,
            'quantity' => 1,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/me/cart/items/{$item->id}", ['quantity' => 4])
            ->assertOk()
            ->assertJsonPath('data.quantity', 4)
            ->assertJsonPath('data.line_total', '100.00');
    }

    public function test_user_cannot_update_other_users_item(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create();
        $item = CartItem::factory()->create([
            'user_id' => $owner->id,
            'design_product_mapping_id' => $mapping->id,
        ]);

        $this->actingAs($other, 'sanctum')
            ->patchJson("/api/me/cart/items/{$item->id}", ['quantity' => 3])
            ->assertForbidden();
    }

    public function test_quantity_validation(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create();
        $item = CartItem::factory()->create([
            'user_id' => $user->id,
            'design_product_mapping_id' => $mapping->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/me/cart/items/{$item->id}", ['quantity' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }
}
