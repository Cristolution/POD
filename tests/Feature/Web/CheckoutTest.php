<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_user_is_redirected_to_login_on_get(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('login'));
    }

    public function test_anonymous_user_is_redirected_to_login_on_post(): void
    {
        $this->post(route('checkout.place'), [
            'address_id' => 1,
            'payment_method' => 'card',
        ])->assertRedirect(route('login'));
    }

    public function test_empty_cart_redirects_to_cart_with_flash(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertRedirect(route('cart.show'))
            ->assertSessionHas('status', 'Your cart is empty.');
    }

    public function test_get_checkout_renders_form_with_addresses_and_payment_methods(): void
    {
        $user = User::factory()->create();
        Address::factory()->for($user)->create();
        DeliveryCompany::factory()->create();
        CartItem::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('checkout.show'));

        $response->assertOk();
        // Summary label + the three payment method radio labels.
        // Text is lowercase in the HTML; visual uppercase comes from the
        // .font-display CSS class — assert case-insensitively.
        $response->assertSee('Summary');
        $response->assertSeeTextInOrder(['cash on delivery', 'bank transfer', 'card']);
    }

    public function test_post_checkout_creates_order_and_payment_and_clears_cart(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        CartItem::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('checkout.place'), [
            'address_id' => $address->id,
            'payment_method' => 'card',
        ]);

        $order = Order::query()->where('customer_id', $user->id)->first();

        $this->assertNotNull($order, 'Order was not created.');
        $response->assertRedirect(route('orders.confirmation', $order));
        $response->assertSessionHas('status', 'Order placed successfully.');

        // Snapshot fields populated.
        $this->assertSame($address->line1, $order->shipping_line1);
        $this->assertSame($address->city, $order->shipping_city);
        $this->assertSame('pending', $order->status);

        // Payment was recorded with the chosen method.
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'card',
            'status' => 'pending',
        ]);

        // Cart was cleared by the action.
        $this->assertDatabaseMissing('cart_items', ['user_id' => $user->id]);
    }

    public function test_post_checkout_rejects_missing_address(): void
    {
        $user = User::factory()->create();
        CartItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('checkout.place'), [
                'address_id' => null,
                'payment_method' => 'card',
            ])
            ->assertSessionHasErrors('address_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_post_checkout_rejects_invalid_payment_method(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        CartItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('checkout.place'), [
                'address_id' => $address->id,
                'payment_method' => 'bitcoin',
            ])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_post_checkout_rejects_address_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherAddress = Address::factory()->for($other)->create();
        CartItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('checkout.place'), [
                'address_id' => $otherAddress->id,
                'payment_method' => 'card',
            ])
            ->assertSessionHasErrors('address_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_get_order_show_renders_for_owner(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $order = Order::factory()
            ->forCustomer($user)
            ->withShippingAddress($address)
            ->pending()
            ->create();

        $response = $this->actingAs($user)
            ->get(route('orders.confirmation', $order))
            ->assertOk();

        $response->assertSee(substr($order->id, 0, 8));
        $response->assertSee('pending');
        $response->assertSee('View all orders');
    }

    public function test_get_order_show_forbids_other_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = Address::factory()->for($owner)->create();
        $order = Order::factory()
            ->forCustomer($owner)
            ->withShippingAddress($address)
            ->pending()
            ->create();

        $this->actingAs($other)
            ->get(route('orders.confirmation', $order))
            ->assertForbidden();
    }

    public function test_get_order_show_404_for_non_existent_order(): void
    {
        $user = User::factory()->create();

        // Valid UUID format but no row in the DB → route model binding 404.
        $missingId = '00000000-0000-0000-0000-000000000000';

        $this->actingAs($user)
            ->get(route('orders.confirmation', $missingId))
            ->assertNotFound();
    }

    public function test_anonymous_order_show_redirects_to_login(): void
    {
        $address = Address::factory()->create();
        $order = Order::factory()
            ->withShippingAddress($address)
            ->pending()
            ->create();

        $this->get(route('orders.confirmation', $order))->assertRedirect(route('login'));
    }

    public function test_post_checkout_runs_in_transaction_so_empty_cart_after_delete_aborts(): void
    {
        // Edge case: user reaches /checkout with one cart item, then deletes it
        // in another tab before submitting. The action must abort(422) and no
        // order/payment must be written.
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        CartItem::factory()->create(['user_id' => $user->id]);

        // Simulate the race: empty the cart before the POST.
        $user->cartItems()->delete();

        $this->actingAs($user)
            ->post(route('checkout.place'), [
                'address_id' => $address->id,
                'payment_method' => 'bank_transfer',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }
}
