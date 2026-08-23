<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Cart;

use App\Models\CartItem;
use App\Models\DesignProductMapping;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_cart_items_with_grand_total(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $template = ProductTemplate::factory()->create();
        $mappingOne = DesignProductMapping::factory()->create([
            'product_template_id' => $template->id,
            'final_price' => 25.00,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_template_id' => $template->id,
            'price_delta' => 5.00,
        ]);
        $mappingTwo = DesignProductMapping::factory()->create([
            'product_template_id' => $template->id,
            'final_price' => 10.00,
        ]);

        CartItem::factory()->create([
            'user_id' => $user->id,
            'design_product_mapping_id' => $mappingOne->id,
            'product_variant_id' => null,
            'quantity' => 2,
        ]);
        CartItem::factory()->create([
            'user_id' => $user->id,
            'design_product_mapping_id' => $mappingTwo->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        // Line totals: 25 * 2 = 50, (10 + 5) * 3 = 45 => grand_total = 95.00
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/cart')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('grand_total', 95);
    }

    public function test_empty_cart_returns_zero_grand_total(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/cart')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('grand_total', 0);
    }

    public function test_anonymous_is_rejected(): void
    {
        $this->getJson('/api/me/cart')->assertStatus(401);
    }
}
