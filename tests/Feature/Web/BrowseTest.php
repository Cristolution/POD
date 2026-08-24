<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_browse_designs_lists_only_published(): void
    {
        Design::factory()->create(['title' => 'Visible', 'status' => 'published']);
        Design::factory()->create(['title' => 'Hidden', 'status' => 'draft']);

        $this->get('/browse/designs')
            ->assertOk()
            ->assertSee('Visible')
            ->assertDontSee('Hidden');
    }

    public function test_browse_designs_filters_by_category(): void
    {
        $cat = Category::factory()->create(['name' => 'Stickers']);
        Design::factory()->create(['title' => 'In', 'status' => 'published', 'category_id' => $cat->id]);
        Design::factory()->create(['title' => 'Out', 'status' => 'published']);

        $this->get('/browse/designs?category='.$cat->id)
            ->assertOk()
            ->assertSee('In')
            ->assertDontSee('Out');
    }

    public function test_browse_designs_searches_by_title(): void
    {
        Design::factory()->create(['title' => 'Cosmic Cat', 'status' => 'published']);
        Design::factory()->create(['title' => 'Plain Puppy', 'status' => 'published']);

        $this->get('/browse/designs?q=Cosmic')
            ->assertOk()
            ->assertSee('Cosmic Cat')
            ->assertDontSee('Plain Puppy');
    }

    public function test_browse_categories_page_lists_root_categories(): void
    {
        $root = Category::factory()->create(['name' => 'Outerwear', 'parent_id' => null]);
        Category::factory()->create(['name' => 'Tee', 'parent_id' => $root->id]);

        $this->get('/browse/categories')
            ->assertOk()
            ->assertSee('Outerwear')
            ->assertDontSee('Tee');
    }

    public function test_browse_designers_page_lists_designers(): void
    {
        $user = User::factory()->create(['name' => 'Avery Designer']);
        DesignerProfile::factory()->create(['user_id' => $user->id]);

        $this->get('/browse/designers')
            ->assertOk()
            ->assertSee('Avery Designer');
    }
}
