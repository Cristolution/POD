<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowseFacetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_by_designer(): void
    {
        $aliceUser = User::factory()->create(['name' => 'Alice Designer']);
        $alice = DesignerProfile::factory()->create(['user_id' => $aliceUser->id]);
        $bobUser = User::factory()->create(['name' => 'Bob Designer']);
        $bob = DesignerProfile::factory()->create(['user_id' => $bobUser->id]);

        Design::factory()->published()->forDesigner($alice)->create(['title' => 'Alice Piece']);
        Design::factory()->published()->forDesigner($bob)->create(['title' => 'Bob Piece']);

        $this->get('/browse/designs?designer='.$alice->id)
            ->assertOk()
            ->assertSee('Alice Piece')
            ->assertDontSee('Bob Piece');
    }

    public function test_filters_by_price_range_includes_design_when_any_mapping_matches(): void
    {
        $design = Design::factory()->published()->create(['title' => 'Mixed Price']);
        $template = ProductTemplate::factory()->create();
        DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'final_price' => 5,
        ]);
        DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => ProductTemplate::factory(),
            'final_price' => 80,
        ]);

        Design::factory()->published()->create(['title' => 'Always Cheap']);

        $this->get('/browse/designs?price_min=50&price_max=100')
            ->assertOk()
            ->assertSee('Mixed Price')
            ->assertDontSee('Always Cheap');
    }

    public function test_filters_by_subcategory(): void
    {
        $root = Category::factory()->root()->create(['name' => 'Apparel']);
        $child = Category::factory()->withParent($root)->create(['name' => 'T-Shirts']);
        $otherRoot = Category::factory()->root()->create(['name' => 'Drinkware']);

        Design::factory()->published()->create(['title' => 'A Tee', 'category_id' => $child->id]);
        Design::factory()->published()->create(['title' => 'A Mug', 'category_id' => $otherRoot->id]);

        $this->get('/browse/designs?category='.$child->id)
            ->assertOk()
            ->assertSee('A Tee')
            ->assertDontSee('A Mug');
    }

    public function test_root_category_filter_includes_descendants(): void
    {
        $root = Category::factory()->root()->create(['name' => 'Apparel']);
        $child = Category::factory()->withParent($root)->create(['name' => 'Hoodies']);
        $otherRoot = Category::factory()->root()->create(['name' => 'Drinkware']);

        Design::factory()->published()->create(['title' => 'A Hoodie', 'category_id' => $child->id]);
        Design::factory()->published()->create(['title' => 'A Mug', 'category_id' => $otherRoot->id]);

        // Filter passing the root should include the descendant's designs.
        $this->get('/browse/designs?category='.$root->id)
            ->assertOk()
            ->assertSee('A Hoodie')
            ->assertDontSee('A Mug');
    }

    public function test_sorts_by_price_ascending(): void
    {
        $cheap = Design::factory()->published()->create(['title' => 'Cheap First']);
        $expensive = Design::factory()->published()->create(['title' => 'Expensive Last']);

        $cheapTemplate = ProductTemplate::factory()->create();
        DesignProductMapping::factory()->create([
            'design_id' => $cheap->id,
            'product_template_id' => $cheapTemplate->id,
            'final_price' => 5,
        ]);
        $expensiveTemplate = ProductTemplate::factory()->create();
        DesignProductMapping::factory()->create([
            'design_id' => $expensive->id,
            'product_template_id' => $expensiveTemplate->id,
            'final_price' => 99,
        ]);

        $response = $this->get('/browse/designs?sort=price_asc')->assertOk();
        $body = $response->getContent();
        $this->assertLessThan(
            strpos($body, 'Expensive Last'),
            strpos($body, 'Cheap First'),
        );
    }

    public function test_card_renders_price_range_and_product_types(): void
    {
        $design = Design::factory()->published()->create(['title' => 'Visual Card']);
        $tshirt = ProductTemplate::factory()->create(['type' => 't-shirt']);
        $mug = ProductTemplate::factory()->create(['type' => 'mug']);
        DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $tshirt->id,
            'final_price' => 12.50,
        ]);
        DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $mug->id,
            'final_price' => 18.00,
        ]);
        ProductVariant::factory()->count(3)->create(['product_template_id' => $tshirt->id, 'is_active' => true]);

        $this->get('/browse/designs')
            ->assertOk()
            ->assertSee('Visual Card')
            ->assertSee('$12.50')
            ->assertSee('$18.00')
            ->assertSee('t shirt')
            ->assertSee('mug')
            ->assertSee('variants');
    }

    public function test_sidebar_renders_root_categories_with_subcategory_links(): void
    {
        $root = Category::factory()->root()->create(['name' => 'Apparel']);
        Category::factory()->withParent($root)->create(['name' => 'Hoodies']);

        $this->get('/browse/designs')
            ->assertOk()
            ->assertSee('Apparel')
            ->assertSee('Hoodies');
    }

    public function test_active_filters_render_as_chips(): void
    {
        $cat = Category::factory()->root()->create(['name' => 'Posters']);

        $this->get('/browse/designs?category='.$cat->id.'&price_min=10&price_max=20')
            ->assertOk()
            ->assertSee('Posters')
            ->assertSee('$10')
            ->assertSee('$20');
    }

    public function test_clear_all_link_drops_all_facets(): void
    {
        $cat = Category::factory()->root()->create(['name' => 'Posters']);
        Design::factory()->published()->create(['title' => 'Other Stuff']);

        // Active chip surfaces the category name and the × remove affordance.
        // The label and × live in separate spans, so we assert on substrings.
        $response = $this->get('/browse/designs?category='.$cat->id)->assertOk();
        $body = $response->getContent();
        $this->assertStringContainsString('Posters', $body);
        $this->assertStringContainsString('×', $body);
        $this->assertStringContainsString('Clear all', $body);

        // The Clear-all link should point to the unfiltered URL (no category=).
        $this->assertMatchesRegularExpression(
            '/href="http[^"]*\/browse\/designs"[^>]*>\s*Clear all/',
            $body,
        );
    }
}
