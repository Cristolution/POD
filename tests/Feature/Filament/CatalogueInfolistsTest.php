<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\Pages\ViewCategory;
use App\Filament\Resources\DesignProductMappings\Pages\ViewDesignProductMapping;
use App\Filament\Resources\Designs\Pages\ViewDesign;
use App\Filament\Resources\ProductTemplates\Pages\ViewProductTemplate;
use App\Filament\Resources\ProductVariants\Pages\ViewProductVariant;
use App\Filament\Resources\Tags\Pages\ViewTag;
use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\DesignProductMapping as DesignProductMappingModel;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Detail-page (View) tests for the 6 catalogue resources.
 *
 * Each infolist is grouped into named sections (Identity, Catalogue, …) with
 * related-data hints (counts, badges, computed prices). These tests prove the
 * sections render with the right content for the resource they belong to.
 */
class CatalogueInfolistsTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Smoke — every detail page must at least render successfully.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Each row: slug, model factory + state, and the matching View page class.
     *
     * @return array<string, array{0: string, 1: class-string}>
     */
    public static function catalogueDetailPages(): array
    {
        return [
            'categories' => [
                'categories/{record}',
                fn () => Category::factory()->create(['name' => 'Apparel']),
                ViewCategory::class,
            ],
            'tags' => [
                'tags/{record}',
                fn () => Tag::factory()->create(['name' => 'vintage']),
                ViewTag::class,
            ],
            'designs' => [
                'designs/{record}',
                fn () => self::makeDesignFixture(),
                ViewDesign::class,
            ],
            'product-templates' => [
                'product-templates/{record}',
                fn () => self::makeProductTemplateFixture(),
                ViewProductTemplate::class,
            ],
            'product-variants' => [
                'product-variants/{record}',
                fn () => self::makeProductVariantFixture(),
                ViewProductVariant::class,
            ],
            'design-product-mappings' => [
                'design-product-mappings/{record}',
                fn () => self::makeDesignProductMappingFixture(),
                ViewDesignProductMapping::class,
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function catalogueSlugs(): array
    {
        $pairs = [];
        foreach (self::catalogueDetailPages() as $label => [$slug, $_factory, $_page]) {
            $pairs[$label] = [$slug];
        }

        return $pairs;
    }

    #[DataProvider('catalogueDetailPages')]
    public function test_admin_can_view_catalogue_detail_page(string $slug, callable $factory, string $pageClass): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $record = $factory();

        $this->actingAs($admin)
            ->get('/admin/'.str_replace('{record}', (string) $record->getKey(), $slug))
            ->assertOk();
    }

    #[DataProvider('catalogueDetailPages')]
    public function test_catalogue_detail_page_renders_via_livewire(string $slug, callable $factory, string $pageClass): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $record = $factory();

        Livewire::actingAs($admin)
            ->test($pageClass, ['record' => $record->getKey()])
            ->assertSuccessful();
    }

    #[DataProvider('catalogueSlugs')]
    public function test_non_admin_cannot_view_catalogue_detail_page(string $slug): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get('/admin/'.$slug)
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Category — Identity + Catalogue + Lifecycle sections
    // ─────────────────────────────────────────────────────────────────────────

    public function test_category_detail_shows_identity_and_catalogue_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = Category::factory()->create(['name' => 'Apparel']);
        $category = Category::factory()->create(['name' => 'T-Shirts', 'parent_id' => $parent->id]);
        Category::factory()->count(2)->create(['parent_id' => $category->id]);
        Design::factory()->count(3)->create(['category_id' => $category->id]);

        $this->actingAs($admin)
            ->get('/admin/categories/'.$category->id)
            ->assertOk()
            ->assertSee('Identity')
            ->assertSee('T-Shirts')
            ->assertSee('Apparel')              // parent name in breadcrumb
            ->assertSee('Catalogue')
            ->assertSee('2 sub-categories')    // children count
            ->assertSee('3 designs')            // designs count
            ->assertSee('Lifecycle');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tag — Identity + Usage hint
    // ─────────────────────────────────────────────────────────────────────────

    public function test_tag_detail_shows_usage_and_lifecycle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tag = Tag::factory()->create(['name' => 'vintage']);
        Design::factory()->count(4)->create()->each(fn ($design) => $design->tags()->attach($tag));

        $this->actingAs($admin)
            ->get('/admin/tags/'.$tag->id)
            ->assertOk()
            ->assertSee('Identity')
            ->assertSee('vintage')
            ->assertSee('Usage')
            ->assertSee('4 designs')
            ->assertSee('Active')               // 4 designs → Active tier (Hot needs ≥10)
            ->assertSee('Lifecycle');
    }

    public function test_tag_detail_shows_hot_popularity_for_high_use(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tag = Tag::factory()->create(['name' => 'trending']);
        Design::factory()->count(12)->create()->each(fn ($design) => $design->tags()->attach($tag));

        $this->actingAs($admin)
            ->get('/admin/tags/'.$tag->id)
            ->assertOk()
            ->assertSee('Hot');                 // ≥10 designs → top-tier hint
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Design — Identity, Attribution, Catalogue
    // ─────────────────────────────────────────────────────────────────────────

    public function test_design_detail_shows_attribution_and_catalogue_sections(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $design = self::makeDesignFixture([
            'title' => 'Sunset Tee',
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->get('/admin/designs/'.$design->id)
            ->assertOk()
            ->assertSee('Identity')
            ->assertSee('Sunset Tee')
            ->assertSee('published')           // status badge
            ->assertSee('Attribution')
            ->assertSee('Catalogue')
            ->assertSee('Lifecycle');
    }

    public function test_design_detail_renders_status_badges_with_correct_color(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $status => $label) {
            $design = self::makeDesignFixture(['status' => $status]);

            $this->actingAs($admin)
                ->get('/admin/designs/'.$design->id)
                ->assertOk()
                ->assertSee($label);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ProductTemplate — Identity, Printer, Pricing, Specs
    // ─────────────────────────────────────────────────────────────────────────

    public function test_product_template_detail_shows_pricing_and_specs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = self::makeProductTemplateFixture([
            'name' => 'A4 Poster Print',
            'base_cost' => 12.50,
            'specs' => ['material' => 'matte', 'dpi' => 300],
        ]);

        $this->actingAs($admin)
            ->get('/admin/product-templates/'.$template->id)
            ->assertOk()
            ->assertSee('Identity')
            ->assertSee('A4 Poster Print')
            ->assertSee('Printer')
            ->assertSee('Pricing')
            ->assertSee('$12.50')
            ->assertSee('Specs')
            ->assertSee('material: matte')
            ->assertSee('dpi: 300')
            ->assertSee('Usage')
            ->assertSee('Lifecycle');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ProductVariant — Identity, Attributes, Pricing, Availability
    // ─────────────────────────────────────────────────────────────────────────

    public function test_product_variant_detail_shows_attributes_and_effective_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $variant = self::makeProductVariantFixture([
            'sku' => 'TSHIRT-BLK-L',
            'attributes' => ['size' => 'L', 'color' => 'black'],
            'price_delta' => 3.00,
        ]);

        // Base cost for the fixture template is 15.00 → effective = 18.00
        $this->actingAs($admin)
            ->get('/admin/product-variants/'.$variant->id)
            ->assertOk()
            ->assertSee('Identity')
            ->assertSee('TSHIRT-BLK-L')
            ->assertSee('Attributes')
            ->assertSee('size: L')
            ->assertSee('color: black')
            ->assertSee('Pricing')
            ->assertSee('$18.00')               // effective price
            ->assertSee('Availability')
            ->assertSee('Active for sale')
            ->assertSee('Lifecycle');
    }

    public function test_product_variant_detail_hides_archived_at_when_not_trashed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $variant = self::makeProductVariantFixture();

        $this->actingAs($admin)
            ->get('/admin/product-variants/'.$variant->id)
            ->assertOk()
            ->assertDontSee('Deleted at');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DesignProductMapping — Identity, Fulfillment, Pricing, Usage
    // ─────────────────────────────────────────────────────────────────────────

    public function test_design_product_mapping_detail_shows_pricing_and_fulfillment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mapping = self::makeDesignProductMappingFixture([
            'final_price' => 22.50,
        ]);

        $this->actingAs($admin)
            ->get('/admin/design-product-mappings/'.$mapping->id)
            ->assertOk()
            ->assertSee('Identity')
            ->assertSee('Fulfillment')
            ->assertSee('Preferred printer')
            ->assertSee('Pricing')
            ->assertSee('$22.50')
            ->assertSee('Usage')
            ->assertSee('Lifecycle');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Fixtures
    // ─────────────────────────────────────────────────────────────────────────

    private static function makeDesignFixture(array $overrides = []): Design
    {
        $designerUser = User::factory()->create(['role' => 'designer']);
        $designer = DesignerProfile::factory()->create(['user_id' => $designerUser->id]);
        $category = Category::factory()->create();

        return Design::factory()->create(array_merge([
            'designer_id' => $designer->id,
            'category_id' => $category->id,
            'title' => 'Sunset Tee',
            'status' => 'published',
        ], $overrides));
    }

    private static function makeProductTemplateFixture(array $overrides = []): ProductTemplate
    {
        $printerUser = User::factory()->create(['role' => 'printer_provider']);
        $printer = PrinterProviderProfile::factory()->create([
            'user_id' => $printerUser->id,
            'company_name' => 'Acme Print Co',
        ]);

        return ProductTemplate::factory()->create(array_merge([
            'printer_provider_id' => $printer->id,
            'name' => 'A4 Poster Print',
            'type' => 'poster',
            'base_cost' => 15.00,
            'specs' => ['material' => 'matte', 'dpi' => 300],
        ], $overrides));
    }

    private static function makeProductVariantFixture(array $overrides = []): ProductVariant
    {
        $template = self::makeProductTemplateFixture(['base_cost' => 15.00]);

        return ProductVariant::factory()->create(array_merge([
            'product_template_id' => $template->id,
            'sku' => 'TSHIRT-BLK-L',
            'attributes' => ['size' => 'L', 'color' => 'black'],
            'price_delta' => 3.00,
            'is_active' => true,
        ], $overrides));
    }

    private static function makeDesignProductMappingFixture(array $overrides = []): DesignProductMappingModel
    {
        $design = self::makeDesignFixture();
        $template = self::makeProductTemplateFixture();
        $printer = PrinterProviderProfile::factory()->create([
            'company_name' => 'Beta Print',
        ]);

        return DesignProductMappingModel::factory()->create(array_merge([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 22.50,
        ], $overrides));
    }
}
