<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\ProductTemplates\Pages\CreateProductTemplate;
use App\Filament\Resources\ProductTemplates\Pages\ListProductTemplates;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductTemplateResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_product_templates(): void
    {
        ProductTemplate::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListProductTemplates::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_product_template(): void
    {
        $printer = PrinterProviderProfile::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateProductTemplate::class)
            ->fillForm([
                'printer_provider_id' => (string) $printer->id,
                'type' => 'mug',
                'base_cost' => 6.50,
                'specs' => [
                    'material' => 'ceramic',
                    'capacity' => '11oz',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('product_templates', [
            'type' => 'mug',
        ]);
    }
}
