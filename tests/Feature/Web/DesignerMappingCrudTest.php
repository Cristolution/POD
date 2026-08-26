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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignerMappingCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: DesignerProfile, 2: Design, 3: ProductTemplate, 4: PrinterProviderProfile}
     */
    private function designerWithResources(): array
    {
        $user = User::factory()->designer()->create();
        $profile = DesignerProfile::factory()->for($user)->create();

        $design = Design::factory()
            ->forDesigner($profile)
            ->for(Category::factory())
            ->create(['title' => 'My Design']);

        $template = ProductTemplate::factory()->create(['name' => 'Classic Tee']);
        $printer = PrinterProviderProfile::factory()->create();

        return [$user, $profile, $design, $template, $printer];
    }

    // ------------------------------------------------------------------
    // Create
    // ------------------------------------------------------------------

    public function test_create_form_renders_with_only_own_designs_in_dropdown(): void
    {
        [$user, , $design] = $this->designerWithResources();

        // Foreign design owned by a different designer — must NOT appear.
        $otherUser = User::factory()->designer()->create();
        $otherProfile = DesignerProfile::factory()->for($otherUser)->create();
        Design::factory()
            ->forDesigner($otherProfile)
            ->for(Category::factory())
            ->create(['title' => 'Foreign Design']);

        $response = $this->actingAs($user)->get(route('designer.mappings.create'));

        $response->assertOk()
            ->assertSee('New mapping')
            ->assertSee('My Design')
            ->assertDontSee('Foreign Design');
    }

    public function test_store_creates_mapping_and_redirects_to_index(): void
    {
        [, , $design, $template, $printer] = $this->designerWithResources();
        $user = $design->designer->user;

        $this->actingAs($user)
            ->post(route('designer.mappings.store'), [
                'design_id' => $design->id,
                'product_template_id' => $template->id,
                'preferred_printer_id' => $printer->id,
                'final_price' => 19.95,
            ])
            ->assertRedirect(route('designer.mappings'))
            ->assertSessionHas('status', 'Mapping created.');

        $this->assertDatabaseHas('design_product_mappings', [
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
        ]);
    }

    public function test_store_rejects_design_owned_by_other_designer(): void
    {
        [, , $ownDesign] = $this->designerWithResources();
        $user = $ownDesign->designer->user;

        // Build a foreign design outside the owner's profile.
        $otherUser = User::factory()->designer()->create();
        $otherProfile = DesignerProfile::factory()->for($otherUser)->create();
        $foreignDesign = Design::factory()
            ->forDesigner($otherProfile)
            ->for(Category::factory())
            ->create();
        $template = ProductTemplate::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();

        $this->actingAs($user)
            ->post(route('designer.mappings.store'), [
                'design_id' => $foreignDesign->id,
                'product_template_id' => $template->id,
                'preferred_printer_id' => $printer->id,
                'final_price' => 19.95,
            ])
            ->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        [, , $design] = $this->designerWithResources();
        $user = $design->designer->user;

        $this->actingAs($user)
            ->from(route('designer.mappings.create'))
            ->post(route('designer.mappings.store'), [
                'design_id' => $design->id,
                // product_template_id intentionally missing
                'preferred_printer_id' => 'irrelevant',
                'final_price' => 10,
            ])
            ->assertRedirect(route('designer.mappings.create'))
            ->assertSessionHasErrors(['product_template_id']);
    }

    // ------------------------------------------------------------------
    // Edit / update
    // ------------------------------------------------------------------

    public function test_edit_form_pre_populates_fields(): void
    {
        [, , $design, $template, $printer] = $this->designerWithResources();
        $user = $design->designer->user;

        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        $response = $this->actingAs($user)->get(route('designer.mappings.edit', $mapping));

        $response->assertOk()
            ->assertSee('Edit mapping')
            ->assertSee((string) $mapping->final_price, false);
    }

    public function test_update_changes_printer_and_price_keeps_immutable_columns(): void
    {
        [, , $design, $template, $printer] = $this->designerWithResources();
        $user = $design->designer->user;

        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        $newPrinter = PrinterProviderProfile::factory()->create();

        $this->actingAs($user)
            ->patch(route('designer.mappings.update', $mapping), [
                'preferred_printer_id' => $newPrinter->id,
                'final_price' => 32.50,
            ])
            ->assertRedirect(route('designer.mappings'))
            ->assertSessionHas('status', 'Mapping updated.');

        $mapping->refresh();
        $this->assertSame($newPrinter->id, $mapping->preferred_printer_id);
        $this->assertSame('32.50', (string) $mapping->final_price);
        $this->assertSame($design->id, $mapping->design_id, 'design_id is immutable after create.');
        $this->assertSame($template->id, $mapping->product_template_id, 'product_template_id is immutable after create.');
    }

    public function test_other_designer_cannot_edit_or_delete(): void
    {
        [$user, , $design, $template, $printer] = $this->designerWithResources();

        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        // A different designer (not the owner) — should be 403.
        $rival = User::factory()->designer()->create();
        DesignerProfile::factory()->for($rival)->create();

        $this->actingAs($rival)
            ->get(route('designer.mappings.edit', $mapping))
            ->assertForbidden();

        $this->actingAs($rival)
            ->patch(route('designer.mappings.update', $mapping), [
                'preferred_printer_id' => $printer->id,
                'final_price' => 99.99,
            ])
            ->assertForbidden();

        $this->actingAs($rival)
            ->delete(route('designer.mappings.destroy', $mapping))
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Destroy
    // ------------------------------------------------------------------

    public function test_destroy_soft_deletes_mapping_and_redirects(): void
    {
        [, , $design, $template, $printer] = $this->designerWithResources();
        $user = $design->designer->user;

        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        $this->actingAs($user)
            ->delete(route('designer.mappings.destroy', $mapping))
            ->assertRedirect(route('designer.mappings'))
            ->assertSessionHas('status', 'Mapping deleted.');

        // Soft-deleted: still in DB but with deleted_at populated.
        $this->assertNotNull(DesignProductMapping::withTrashed()->find($mapping->id)?->deleted_at);
    }

    public function test_destroy_fails_when_mapping_has_order_items(): void
    {
        [, , $design, $template, $printer] = $this->designerWithResources();
        $user = $design->designer->user;

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
            'printer_provider_id' => $printer->id,
            'unit_price' => 25.00,
        ]);

        $response = $this->actingAs($user)
            ->from(route('designer.mappings'))
            ->delete(route('designer.mappings.destroy', $mapping));

        $response->assertRedirect(route('designer.mappings'))
            ->assertSessionHasErrors(['mapping']);

        // Mapping is still present (not soft-deleted).
        $this->assertNull(DesignProductMapping::withTrashed()->find($mapping->id)?->deleted_at);
    }

    // ------------------------------------------------------------------
    // Auth gates
    // ------------------------------------------------------------------

    public function test_anonymous_request_to_create_redirects_to_login(): void
    {
        $this->get(route('designer.mappings.create'))->assertRedirect(route('login'));
    }

    public function test_anonymous_request_to_store_redirects_to_login(): void
    {
        $this->post(route('designer.mappings.store'), [])->assertRedirect(route('login'));
    }

    public function test_anonymous_request_to_edit_redirects_to_login(): void
    {
        [, , $design, $template, $printer] = $this->designerWithResources();
        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        $this->get(route('designer.mappings.edit', $mapping))->assertRedirect(route('login'));
    }

    public function test_anonymous_request_to_update_redirects_to_login(): void
    {
        [, , $design, $template, $printer] = $this->designerWithResources();
        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        $this->patch(route('designer.mappings.update', $mapping), [
            'final_price' => 1.00,
        ])->assertRedirect(route('login'));
    }

    public function test_anonymous_request_to_destroy_redirects_to_login(): void
    {
        [, , $design, $template, $printer] = $this->designerWithResources();
        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        $this->delete(route('designer.mappings.destroy', $mapping))->assertRedirect(route('login'));
    }

    public function test_customer_role_is_forbidden_from_create(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('designer.mappings.create'))
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Index page surface
    // ------------------------------------------------------------------

    public function test_index_lists_mappings_with_edit_and_delete_buttons(): void
    {
        [, , $design, $template, $printer] = $this->designerWithResources();
        $user = $design->designer->user;

        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        $response = $this->actingAs($user)->get(route('designer.mappings'));

        $response->assertOk()
            ->assertSee('Edit')
            ->assertSee('Delete')
            ->assertSee(route('designer.mappings.edit', $mapping), false)
            ->assertSee(route('designer.mappings.destroy', $mapping), false);
    }

    public function test_index_shows_create_cta_when_no_mappings(): void
    {
        [$user] = $this->designerWithResources();

        $this->actingAs($user)
            ->get(route('designer.mappings'))
            ->assertOk()
            ->assertSee(route('designer.mappings.create'), false);
    }
}
