<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignerMappingIndexTest extends TestCase
{
    use RefreshDatabase;

    private function designerWithMapping(): array
    {
        $designer = User::factory()->designer()->create();
        $profile = DesignerProfile::factory()->for($designer)->create();

        $design = Design::factory()
            ->forDesigner($profile)
            ->for(Category::factory())
            ->create(['title' => 'My Design']);

        $template = ProductTemplate::factory()->create(['type' => 't-shirt']);
        $printer = PrinterProviderProfile::factory()->create();

        DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        return [$designer, $profile, $design];
    }

    private function otherDesignersMapping(): void
    {
        $otherUser = User::factory()->designer()->create();
        $otherProfile = DesignerProfile::factory()->for($otherUser)->create();

        $otherDesign = Design::factory()
            ->forDesigner($otherProfile)
            ->for(Category::factory())
            ->create();

        $template = ProductTemplate::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();

        DesignProductMapping::factory()->create([
            'design_id' => $otherDesign->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);
    }

    public function test_mappings_index_shows_own_mappings(): void
    {
        [$designer] = $this->designerWithMapping();

        $this->actingAs($designer)
            ->get(route('designer.mappings'))
            ->assertOk()
            ->assertSee('My Design')
            ->assertSee('t-shirt');
    }

    public function test_mappings_index_excludes_other_designers_mappings(): void
    {
        [$designer] = $this->designerWithMapping();
        $this->otherDesignersMapping();

        $this->actingAs($designer)
            ->get(route('designer.mappings'))
            ->assertOk()
            ->assertSee('t-shirt');

        $this->assertSame(2, DesignProductMapping::query()->count(), 'Both mappings should exist in DB.');
        // The other designer's product template has a unique random name, so the
        // index page must not contain it.
        $otherMappingTemplate = DesignProductMapping::query()
            ->whereHas('design', fn ($q) => $q->where('designer_id', '!=', $designer->designerProfile->id))
            ->with('productTemplate')
            ->first();
        $this->actingAs($designer)
            ->get(route('designer.mappings'))
            ->assertDontSee($otherMappingTemplate->productTemplate->name);
    }

    public function test_mappings_index_requires_authentication(): void
    {
        $this->get(route('designer.mappings'))->assertRedirect(route('login'));
    }
}
