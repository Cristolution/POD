<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the form-encoded POST string/int mismatch in cart.
 *
 * Background: Laravel's `integer` validation rule parses but does not auto-cast.
 * When a real browser POSTs `quantity=3`, the validator passes (it parses as
 * int) but `$data['quantity']` is the raw string `"3"` from the request bag.
 * The old CartController passed that straight into {@see
 * \App\Actions\Cart\UpsertCartItemAction::execute()} whose signature is
 * `int $quantity`, so the action threw a TypeError and the user got a 500.
 *
 * Pre-fix: $this->post(...) with `'quantity' => '3'` raises TypeError → 500.
 * Post-fix: $this->post(...) with `'quantity' => '3'` redirects with success.
 */
class CartControllerIntegerQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_accepts_string_quantity_from_form_post(): void
    {
        $user = User::factory()->customer()->create();
        $mapping = DesignProductMapping::factory()->create();
        $variant = ProductVariant::factory()->create();

        // String, as a real form-encoded POST delivers it.
        $this->actingAs($user)
            ->post(route('cart.items.store'), [
                'design_product_mapping_id' => $mapping->id,
                'product_variant_id' => $variant->id,
                'quantity' => '3',
            ])
            ->assertRedirect(route('cart.show'))
            ->assertSessionHas('status', 'Added to cart.');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'design_product_mapping_id' => $mapping->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
    }

    public function test_update_accepts_string_quantity_from_form_post(): void
    {
        $user = User::factory()->customer()->create();
        $existing = CartItem::factory()->create([
            'user_id' => $user->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('cart.items.update', $existing), [
                'quantity' => '5',
            ])
            ->assertRedirect(route('cart.show'));

        $this->assertDatabaseHas('cart_items', [
            'id' => $existing->id,
            'quantity' => 5,
        ]);
    }
}
