<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Mappings;

use App\Models\Design;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use App\Models\User;
use Database\Factories\DesignerProfileFactory;
use Database\Factories\DesignProductMappingFactory;
use Database\Factories\ProductTemplateFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    private function makeDesignerWithProfile(): array
    {
        $user = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfileFactory::new()->for($user, 'user')->create();

        return [$user, $profile];
    }

    public function test_customer_cannot_create_mapping(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $design = Design::factory()->create();
        $template = ProductTemplateFactory::new()->create();
        $printer = PrinterProviderProfile::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/me/mappings', [
                'design_id' => $design->id,
                'product_template_id' => $template->id,
                'preferred_printer_id' => $printer->id,
                'final_price' => 19.99,
            ])
            ->assertForbidden();
    }

    public function test_designer_can_create_mapping_for_own_design(): void
    {
        [$designer] = $this->makeDesignerWithProfile();
        $design = Design::factory()->forDesigner($designer->designerProfile)->create();
        $template = ProductTemplateFactory::new()->create();
        $printer = PrinterProviderProfile::factory()->create();

        $this->actingAs($designer, 'sanctum')
            ->postJson('/api/me/mappings', [
                'design_id' => $design->id,
                'product_template_id' => $template->id,
                'preferred_printer_id' => $printer->id,
                'final_price' => 29.99,
            ])
            ->assertCreated()
            ->assertJsonPath('data.final_price', 29.99);

        $this->assertDatabaseHas('design_product_mappings', [
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'final_price' => 29.99,
        ]);
    }

    public function test_designer_cannot_create_mapping_for_other_designers_design(): void
    {
        [$designer] = $this->makeDesignerWithProfile();

        $otherDesigner = User::factory()->create(['role' => 'designer']);
        $otherProfile = DesignerProfileFactory::new()->for($otherDesigner, 'user')->create();
        $otherDesign = Design::factory()->forDesigner($otherProfile)->create();
        $template = ProductTemplateFactory::new()->create();
        $printer = PrinterProviderProfile::factory()->create();

        $this->actingAs($designer, 'sanctum')
            ->postJson('/api/me/mappings', [
                'design_id' => $otherDesign->id,
                'product_template_id' => $template->id,
                'preferred_printer_id' => $printer->id,
                'final_price' => 19.99,
            ])
            ->assertForbidden();
    }

    public function test_designer_can_update_own_mapping(): void
    {
        [$designer] = $this->makeDesignerWithProfile();
        $design = Design::factory()->forDesigner($designer->designerProfile)->create();
        $mapping = DesignProductMappingFactory::new()->for($design, 'design')->create();

        $this->actingAs($designer, 'sanctum')
            ->patchJson("/api/me/mappings/{$mapping->id}", [
                'final_price' => 42.50,
            ])
            ->assertOk()
            ->assertJsonPath('data.final_price', 42.50);

        $this->assertSame('42.50', $mapping->fresh()->final_price);
    }

    public function test_delete_returns_409_when_order_items_exist(): void
    {
        [$designer] = $this->makeDesignerWithProfile();
        $design = Design::factory()->forDesigner($designer->designerProfile)->create();
        $mapping = DesignProductMappingFactory::new()->for($design, 'design')->create();
        OrderItem::factory()->for($mapping, 'designProductMapping')->create();

        $this->actingAs($designer, 'sanctum')
            ->deleteJson("/api/me/mappings/{$mapping->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('design_product_mappings', ['id' => $mapping->id]);
    }

    public function test_designer_can_delete_own_mapping(): void
    {
        [$designer] = $this->makeDesignerWithProfile();
        $design = Design::factory()->forDesigner($designer->designerProfile)->create();
        $mapping = DesignProductMappingFactory::new()->for($design, 'design')->create();

        $this->actingAs($designer, 'sanctum')
            ->deleteJson("/api/me/mappings/{$mapping->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('design_product_mappings', ['id' => $mapping->id]);
    }
}
