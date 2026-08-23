<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Cart;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_item(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create();
        $item = CartItem::factory()->create([
            'user_id' => $user->id,
            'design_product_mapping_id' => $mapping->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/me/cart/items/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_user_cannot_delete_other_users_item(): void
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
            ->deleteJson("/api/me/cart/items/{$item->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    public function test_anonymous_is_rejected(): void
    {
        ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create();
        $item = CartItem::factory()->create([
            'design_product_mapping_id' => $mapping->id,
        ]);

        $this->deleteJson("/api/me/cart/items/{$item->id}")
            ->assertStatus(401);
    }
}
