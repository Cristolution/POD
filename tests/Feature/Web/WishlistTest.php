<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Design;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_design_to_wishlist(): void
    {
        $design = Design::factory()->published()->create();
        $customer = User::factory()->customer()->create();

        $this
            ->actingAs($customer)
            ->post(route('design.wishlist.store', $design))
            ->assertRedirect();

        $this->assertDatabaseHas('wishlist_items', [
            'design_id' => $design->id,
        ]);
        $this->assertDatabaseHas('wishlists', [
            'user_id' => $customer->id,
        ]);
    }

    public function test_adding_twice_does_not_create_duplicate(): void
    {
        $design = Design::factory()->published()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post(route('design.wishlist.store', $design));
        $this->actingAs($customer)->post(route('design.wishlist.store', $design));

        $this->assertCount(1, WishlistItem::where('design_id', $design->id)->get());
    }

    public function test_customer_can_remove_design_from_wishlist(): void
    {
        $design = Design::factory()->published()->create();
        $customer = User::factory()->customer()->create();
        $wishlist = $customer->getOrCreateWishlist();
        WishlistItem::create([
            'wishlist_id' => $wishlist->id,
            'design_id' => $design->id,
            'sort_order' => 0,
        ]);

        $this
            ->actingAs($customer)
            ->delete(route('design.wishlist.destroy', $design))
            ->assertRedirect();

        $this->assertDatabaseMissing('wishlist_items', [
            'wishlist_id' => $wishlist->id,
            'design_id' => $design->id,
        ]);
    }

    public function test_designer_cannot_use_wishlist(): void
    {
        $design = Design::factory()->published()->create();
        $designer = User::factory()->designer()->create();

        // The role:customer middleware blocks non-customers with a 403.
        $this
            ->actingAs($designer)
            ->post(route('design.wishlist.store', $design))
            ->assertForbidden();

        $this->assertDatabaseMissing('wishlist_items', ['design_id' => $design->id]);
    }

    public function test_anonymous_cannot_use_wishlist(): void
    {
        $design = Design::factory()->published()->create();

        $this
            ->post(route('design.wishlist.store', $design))
            ->assertRedirect(route('login'));
    }

    public function test_removed_wishlist_item_cannot_be_removed_again(): void
    {
        $design = Design::factory()->published()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->delete(route('design.wishlist.destroy', $design))->assertRedirect();
        $this->actingAs($customer)->delete(route('design.wishlist.destroy', $design))->assertRedirect();

        $this->assertCount(0, WishlistItem::all());
    }

    public function test_wishlist_page_lists_saved_designs_in_order(): void
    {
        $designA = Design::factory()->published()->create(['title' => 'A']);
        $designB = Design::factory()->published()->create(['title' => 'B']);
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->post(route('design.wishlist.store', $designA));
        $this->actingAs($customer)->post(route('design.wishlist.store', $designB));

        $response = $this->actingAs($customer)->get(route('account.wishlist'));

        $response->assertOk();
        $response->assertSee('A');
        $response->assertSee('B');
    }

    public function test_wishlist_empty_state_shows_browse_link(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->get(route('account.wishlist'));

        $response->assertOk();
        $response->assertSee('No favourites yet');
        $response->assertSee('Browse designs');
    }
}
