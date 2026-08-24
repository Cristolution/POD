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

    public function test_header_shows_user_name_when_authenticated(): void
    {
        $user = User::factory()->create(['name' => 'Cart Tester']);
        CartItem::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Cart Tester', 'Cart']);
    }
}
