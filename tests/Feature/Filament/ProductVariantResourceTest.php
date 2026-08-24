<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\ProductVariants\Pages\CreateProductVariant;
use App\Filament\Resources\ProductVariants\Pages\ListProductVariants;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductVariantResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_product_variants(): void
    {
        ProductVariant::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListProductVariants::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_product_variant(): void
    {
        $template = ProductTemplate::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateProductVariant::class)
            ->fillForm([
                'product_template_id' => (string) $template->id,
                'attributes' => [
                    'size' => 'L',
                    'color' => 'black',
                ],
                'price_delta' => 2.00,
                'sku' => 'MUG-L-BLK',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'MUG-L-BLK',
            'is_active' => true,
        ]);
    }
}
