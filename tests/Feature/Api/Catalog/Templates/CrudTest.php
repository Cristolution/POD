<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Templates;

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

    public function test_anonymous_is_rejected_on_store(): void
    {
        $this->postJson('/api/me/templates', [
            'name' => 'Mug',
            'type' => 'mug',
            'base_cost' => 9.99,
        ])->assertStatus(401);
    }

    public function test_customer_cannot_create_template(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/me/templates', [
                'name' => 'Mug',
                'type' => 'mug',
                'base_cost' => 9.99,
            ])
            ->assertForbidden();
    }

    public function test_printer_can_create_template(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);

        $this->actingAs($printer, 'sanctum')
            ->postJson('/api/me/templates', [
                'name' => 'Classic Mug',
                'type' => 'mug',
                'base_cost' => 12.50,
                'specs' => ['material' => 'ceramic'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Classic Mug')
            ->assertJsonPath('data.base_cost', 12.50);

        $this->assertDatabaseHas('product_templates', [
            'name' => 'Classic Mug',
            'type' => 'mug',
            'base_cost' => 12.50,
        ]);
    }

    public function test_printer_can_update_own_template(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);
        $profile = PrinterProviderProfileFactory::new()->for($printer, 'user')->create();
        $template = ProductTemplateFactory::new()->for($profile, 'printerProvider')->create();

        $this->actingAs($printer, 'sanctum')
            ->patchJson("/api/me/templates/{$template->id}", [
                'name' => 'Renamed Mug',
                'base_cost' => 14.50,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Mug')
            ->assertJsonPath('data.base_cost', 14.50);

        $this->assertSame('Renamed Mug', $template->fresh()->name);
    }

    public function test_printer_cannot_update_other_printers_template(): void
    {
        $owner = User::factory()->create(['role' => 'printer_provider']);
        $ownerProfile = PrinterProviderProfileFactory::new()->for($owner, 'user')->create();
        $template = ProductTemplateFactory::new()->for($ownerProfile, 'printerProvider')->create();

        $other = User::factory()->create(['role' => 'printer_provider']);

        $this->actingAs($other, 'sanctum')
            ->patchJson("/api/me/templates/{$template->id}", [
                'name' => 'Hijacked',
            ])
            ->assertForbidden();
    }

    public function test_designer_cannot_update_template(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);
        $profile = PrinterProviderProfileFactory::new()->for($printer, 'user')->create();
        $template = ProductTemplateFactory::new()->for($profile, 'printerProvider')->create();

        $designer = User::factory()->create(['role' => 'designer']);

        $this->actingAs($designer, 'sanctum')
            ->patchJson("/api/me/templates/{$template->id}", [
                'name' => 'Designer Override',
            ])
            ->assertForbidden();
    }

    public function test_printer_can_delete_own_template(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);
        $profile = PrinterProviderProfileFactory::new()->for($printer, 'user')->create();
        $template = ProductTemplateFactory::new()->for($profile, 'printerProvider')->create();

        $this->actingAs($printer, 'sanctum')
            ->deleteJson("/api/me/templates/{$template->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('product_templates', ['id' => $template->id]);
    }

    public function test_printer_cannot_delete_template_with_order_items(): void
    {
        $printer = User::factory()->create(['role' => 'printer_provider']);
        $profile = PrinterProviderProfileFactory::new()->for($printer, 'user')->create();
        $template = ProductTemplateFactory::new()->for($profile, 'printerProvider')->create();
        $variant = ProductVariantFactory::new()->for($template, 'productTemplate')->create();
        OrderItem::factory()->for($profile, 'printerProvider')->for($variant, 'productVariant')->create();

        $this->actingAs($printer, 'sanctum')
            ->deleteJson("/api/me/templates/{$template->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('product_templates', ['id' => $template->id]);
    }
}
