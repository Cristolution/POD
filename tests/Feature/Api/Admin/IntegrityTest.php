<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Models\CartItem;
use App\Models\Design;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_mismatches_returns_designers_without_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $designerWithoutProfile = User::factory()->create(['role' => 'designer']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/integrity/profile-mismatches')
            ->assertOk();

        $response->assertJsonPath('designers_without_profile.0.id', $designerWithoutProfile->id);
        $this->assertCount(1, $response->json('designers_without_profile'));
    }

    public function test_orphaned_media_returns_media_with_missing_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $design = Design::factory()->create();
        $media = Media::factory()->create([
            'model_type' => Design::class,
            'model_id' => $design->id,
        ]);

        // Force-delete the design so the morphTo owner resolves to null.
        $design->forceDelete();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/integrity/orphaned-media')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($media->id, $ids);
    }

    public function test_items_without_shipment_returns_handed_off_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $order = Order::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();

        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'printer_provider_id' => $printer->id,
            'status' => 'handed_off',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/integrity/items-without-shipment')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($item->id, $ids);
    }

    public function test_stuck_cart_items_returns_inactive_variant_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $variant = ProductVariant::factory()->create();
        $cartItem = CartItem::factory()->create([
            'user_id' => $customer->id,
            'product_variant_id' => $variant->id,
        ]);

        // Soft-delete the variant so the cart line becomes stuck.
        $variant->delete();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/integrity/stuck-cart-items')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($cartItem->id, $ids);
    }
}
