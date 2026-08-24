<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\DesignProductMappings\Pages\CreateDesignProductMapping;
use App\Filament\Resources\DesignProductMappings\Pages\ListDesignProductMappings;
use App\Models\Design;
use App\Models\DesignProductMapping;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DesignProductMappingResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_design_product_mappings(): void
    {
        DesignProductMapping::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListDesignProductMappings::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_design_product_mapping(): void
    {
        $design = Design::factory()->create();
        $template = ProductTemplate::factory()->create();
        $printer = PrinterProviderProfile::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateDesignProductMapping::class)
            ->fillForm([
                'design_id' => (string) $design->id,
                'product_template_id' => (string) $template->id,
                'preferred_printer_id' => (string) $printer->id,
                'final_price' => 25.00,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('design_product_mappings', [
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'final_price' => '25.00',
        ]);
    }
}
