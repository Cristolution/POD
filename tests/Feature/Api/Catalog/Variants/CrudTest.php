<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Variants;

use App\Models\OrderItem;
use App\Models\User;
use Database\Factories\PrinterProviderProfileFactory;
use Database\Factories\ProductTemplateFactory;
use Database\Factories\ProductVariantFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    private function makePrinterWithTemplate(): array
    {
        $user = User::factory()->create(['role' => 'printer_provider']);
        $profile = PrinterProviderProfileFactory::new()->for($user, 'user')->create();
        $template = ProductTemplateFactory::new()->for($profile, 'printerProvider')->create();

        return [$user, $profile, $template];
    }

    public function test_anonymous_can_list_variants_for_template(): void
    {
        $template = ProductTemplateFactory::new()->create();
        ProductVariantFactory::new()->count(2)->for($template, 'productTemplate')->create();

        $this->getJson("/api/templates/{$template->id}/variants")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_cannot_create_variant(): void
    {
        [, , $template] = $this->makePrinterWithTemplate();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/me/templates/{$template->id}/variants", [
                'sku' => 'TEST-001',
                'attributes' => ['size' => 'M'],
            ])
            ->assertForbidden();
    }

    public function test_printer_can_create_variant_on_own_template(): void
    {
        [$printer, , $template] = $this->makePrinterWithTemplate();

        $this->actingAs($printer, 'sanctum')
            ->postJson("/api/me/templates/{$template->id}/variants", [
                'sku' => 'MUG-RED-LG',
                'attributes' => ['size' => 'L', 'color' => 'red'],
                'price_delta' => 2.50,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.sku', 'MUG-RED-LG')
            ->assertJsonPath('data.label', 'L / red');

        $this->assertDatabaseHas('product_variants', [
            'product_template_id' => $template->id,
            'sku' => 'MUG-RED-LG',
        ]);
    }

    public function test_printer_cannot_create_variant_on_other_printers_template(): void
    {
        [, , $template] = $this->makePrinterWithTemplate();
        $other = User::factory()->create(['role' => 'printer_provider']);

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/me/templates/{$template->id}/variants", [
                'sku' => 'MUG-OTHER',
                'attributes' => ['size' => 'M'],
            ])
            ->assertForbidden();
    }

    public function test_printer_can_update_own_variant(): void
    {
        [$printer, , $template] = $this->makePrinterWithTemplate();
        $variant = ProductVariantFactory::new()->for($template, 'productTemplate')->create();

        $this->actingAs($printer, 'sanctum')
            ->patchJson("/api/me/variants/{$variant->id}", [
                'price_delta' => 3.25,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.price_delta', 3.25)
            ->assertJsonPath('data.is_active', false);

        $fresh = $variant->fresh();
        $this->assertSame('3.25', $fresh->price_delta);
        $this->assertFalse($fresh->is_active);
    }

    public function test_printer_cannot_update_other_printers_variant(): void
    {
        [, , $template] = $this->makePrinterWithTemplate();
        $variant = ProductVariantFactory::new()->for($template, 'productTemplate')->create();
        $other = User::factory()->create(['role' => 'printer_provider']);

        $this->actingAs($other, 'sanctum')
            ->patchJson("/api/me/variants/{$variant->id}", [
                'price_delta' => 99.99,
            ])
            ->assertForbidden();
    }

    public function test_printer_can_delete_own_variant(): void
    {
        [$printer, , $template] = $this->makePrinterWithTemplate();
        $variant = ProductVariantFactory::new()->for($template, 'productTemplate')->create();

        $this->actingAs($printer, 'sanctum')
            ->deleteJson("/api/me/variants/{$variant->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
    }

    public function test_printer_cannot_delete_variant_with_order_items(): void
    {
        [$printer, $profile, $template] = $this->makePrinterWithTemplate();
        $variant = ProductVariantFactory::new()->for($template, 'productTemplate')->create();
        OrderItem::factory()->for($profile, 'printerProvider')->for($variant, 'productVariant')->create();

        $this->actingAs($printer, 'sanctum')
            ->deleteJson("/api/me/variants/{$variant->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);
    }
}
