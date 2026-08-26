<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignerOrderIndexTest extends TestCase
{
    use RefreshDatabase;

    private function designerWithOrderItem(): array
    {
        $designer = User::factory()->designer()->create();
        $profile = DesignerProfile::factory()->for($designer)->create();

        $design = Design::factory()
            ->forDesigner($profile)
            ->for(Category::factory())
            ->create(['title' => 'My Design']);

        $template = ProductTemplate::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();
        $variant = ProductVariant::factory()->for($template)->create();

        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        $customer = User::factory()->customer()->create();
        $order = Order::factory()->forCustomer($customer)->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'design_product_mapping_id' => $mapping->id,
            'product_variant_id' => $variant->id,
            'printer_provider_id' => $printer->id,
            'quantity' => 2,
            'unit_price' => 25.00,
        ]);

        return [$designer, $profile, $design, $order];
    }

    private function otherDesignersOrderItem(DesignerProfile $ownProfile): void
    {
        $otherUser = User::factory()->designer()->create();
        $otherProfile = DesignerProfile::factory()->for($otherUser)->create();

        $otherDesign = Design::factory()
            ->forDesigner($otherProfile)
            ->for(Category::factory())
            ->create();

        $template = ProductTemplate::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();
        $variant = ProductVariant::factory()->for($template)->create();

        $otherMapping = DesignProductMapping::factory()->create([
            'design_id' => $otherDesign->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        $customer = User::factory()->customer()->create();
        $order = Order::factory()->forCustomer($customer)->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'design_product_mapping_id' => $otherMapping->id,
            'product_variant_id' => $variant->id,
            'printer_provider_id' => $printer->id,
            'quantity' => 1,
            'unit_price' => 25.00,
        ]);
    }

    public function test_orders_index_shows_own_orders(): void
    {
        [$designer] = $this->designerWithOrderItem();

        $this->actingAs($designer)
            ->get(route('designer.orders'))
            ->assertOk()
            ->assertSee('My Design');
    }

    public function test_orders_index_excludes_other_designers_orders(): void
    {
        [$designer, $profile] = $this->designerWithOrderItem();
        $this->otherDesignersOrderItem($profile);

        $response = $this->actingAs($designer)->get(route('designer.orders'));
        $response->assertOk();

        // 2 items in DB total; the controller's whereHas restricts to 1 row visible.
        $this->assertSame(2, OrderItem::query()->count());
        $visibleCount = substr_count($response->getContent(), '$25.00');
        $this->assertSame(1, $visibleCount, 'Should only see own one order item.');
    }

    public function test_orders_index_requires_authentication(): void
    {
        $this->get(route('designer.orders'))->assertRedirect(route('login'));
    }

    public function test_orders_index_404_when_user_has_no_designer_profile(): void
    {
        $user = User::factory()->designer()->create(); // role=designer but no DesignerProfile

        $this->actingAs($user)
            ->get(route('designer.orders'))
            ->assertNotFound();
    }
}
