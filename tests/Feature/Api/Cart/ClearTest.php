<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Cart;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_clear_cart(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);
        ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create();
        $otherMapping = DesignProductMapping::factory()->create();

        CartItem::factory()->create([
            'user_id' => $user->id,
            'design_product_mapping_id' => $mapping->id,
            'quantity' => 2,
        ]);
        CartItem::factory()->create([
            'user_id' => $user->id,
            'design_product_mapping_id' => $otherMapping->id,
            'quantity' => 3,
        ]);
        // Belongs to a different user — must NOT be removed.
        CartItem::factory()->create([
            'user_id' => $other->id,
            'design_product_mapping_id' => $mapping->id,
            'quantity' => 5,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/me/cart')
            ->assertNoContent();

        $this->assertDatabaseMissing('cart_items', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $other->id,
            'design_product_mapping_id' => $mapping->id,
            'quantity' => 5,
        ]);
    }
}
