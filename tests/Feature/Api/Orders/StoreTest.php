<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Orders;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_place_order_from_cart(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'customer']);
        $otherUser = User::factory()->create(['role' => 'customer']);

        ProductTemplate::factory()->create();
        $mappingOne = DesignProductMapping::factory()->create(['final_price' => 25.00]);
        $mappingTwo = DesignProductMapping::factory()->create(['final_price' => 10.00]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'design_product_mapping_id' => $mappingOne->id,
            'quantity' => 2,
        ]);
        CartItem::factory()->create([
            'user_id' => $user->id,
            'design_product_mapping_id' => $mappingTwo->id,
            'quantity' => 3,
        ]);
        // Belongs to another user — must NOT be included.
        CartItem::factory()->create([
            'user_id' => $otherUser->id,
            'design_product_mapping_id' => $mappingOne->id,
            'quantity' => 7,
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'shipping_address_id' => $address->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_amount', 80)
            ->assertJsonPath('data.customer_id', $user->id)
            ->assertJsonPath('data.shipping_line1', $address->line1)
            ->assertJsonCount(2, 'data.items');

        // Cart for this user is cleared; the other user's cart is untouched.
        $this->assertDatabaseMissing('cart_items', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', ['user_id' => $otherUser->id]);

        // Exactly one order + exactly two items exist.
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);

        // Notification dispatched to the ordering user.
        Notification::assertSentTo($user, OrderPlacedNotification::class);
    }

    public function test_user_cannot_place_order_with_empty_cart(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'shipping_address_id' => $address->id,
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_user_cannot_use_another_users_shipping_address(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        ProductTemplate::factory()->create();
        $mapping = DesignProductMapping::factory()->create();

        CartItem::factory()->create([
            'user_id' => $user->id,
            'design_product_mapping_id' => $mapping->id,
            'quantity' => 1,
        ]);

        // Address belongs to a different user.
        $otherAddress = Address::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'shipping_address_id' => $otherAddress->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('shipping_address_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_anonymous_is_rejected(): void
    {
        $address = Address::factory()->create();

        $this->postJson('/api/orders', [
            'shipping_address_id' => $address->id,
        ])->assertStatus(401);
    }
}
