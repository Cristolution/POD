<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\CartItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_user_is_redirected_to_login(): void
    {
        $this->get('/cart')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_cart(): void
    {
        $user = User::factory()->create();
        $item = CartItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/cart')
            ->assertOk()
            ->assertSee($item->designProductMapping->design->title);
    }

    public function test_remove_button_works(): void
    {
        $user = User::factory()->create();
        $item = CartItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('cart.items.destroy', $item))
            ->assertRedirect(route('cart.show'));

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_cannot_remove_other_users_cart_item(): void
    {
        $other = User::factory()->create();
        $item = CartItem::factory()->create(['user_id' => $other->id]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('cart.items.destroy', $item))
            ->assertForbidden();
    }

    public function test_clear_cart_redirects(): void
    {
        $user = User::factory()->create();
        CartItem::factory()->count(2)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('cart.clear'))
            ->assertRedirect(route('cart.show'))
            ->assertSessionHas('status', 'Cart cleared.');
    }

    public function test_header_renders_for_authenticated_user_with_cart_items(): void
    {
        $user = User::factory()->create(['name' => 'Cart Tester']);
        CartItem::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get('/')
            ->assertOk();

        $response->assertSeeInOrder(['Cart Tester', 'Cart']);
        // View::share() of cartCount is interpolated server-side into the
        // Alpine x-data attribute on the header cart badge.
        $response->assertSee('count: 1', escape: false);
    }

    public function test_adding_quantity_over_existing_line_caps_at_100(): void
    {
        $user = User::factory()->create();
        $existing = CartItem::factory()->create([
            'user_id' => $user->id,
            'quantity' => 95,
        ]);
        $mappingId = $existing->design_product_mapping_id;
        $variantId = $existing->product_variant_id;

        $this->actingAs($user)->post(route('cart.items.store'), [
            'design_product_mapping_id' => $mappingId,
            'product_variant_id' => $variantId,
            'quantity' => 10,
        ])->assertRedirect(route('cart.show'));

        $this->assertDatabaseHas('cart_items', [
            'id' => $existing->id,
            'quantity' => 100,
        ]);
    }

    public function test_store_rejects_non_uuid_mapping_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'design_product_mapping_id' => 'not-a-uuid',
            'product_variant_id' => null,
            'quantity' => 1,
        ])->assertSessionHasErrors('design_product_mapping_id');
    }
}
