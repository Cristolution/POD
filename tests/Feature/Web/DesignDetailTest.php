<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_design_page_renders(): void
    {
        $design = Design::factory()
            ->for(Category::factory(), 'category')
            ->published()
            ->create(['title' => 'Visible Design']);

        $this->get('/designs/'.$design->id)
            ->assertOk()
            ->assertSee('Visible Design');
    }

    public function test_draft_design_returns_404_for_anonymous(): void
    {
        $design = Design::factory()
            ->draft()
            ->create();

        $this->get('/designs/'.$design->id)->assertNotFound();
    }

    public function test_designer_can_view_own_draft(): void
    {
        // Designer (owner) can view own drafts even when anonymous cannot.
        $user = User::factory()->create();
        $designerProfile = DesignerProfile::factory()->create(['user_id' => $user->id]);
        $design = Design::factory()
            ->draft()
            ->forDesigner($designerProfile)
            ->create(['title' => 'My Draft']);

        $this->actingAs($user)
            ->get('/designs/'.$design->id)
            ->assertOk()
            ->assertSee('My Draft');
    }

    public function test_admin_can_view_drafts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $design = Design::factory()->draft()->create(['title' => 'Hidden But Admin']);

        $this->actingAs($admin)
            ->get('/designs/'.$design->id)
            ->assertOk()
            ->assertSee('Hidden But Admin');
    }

    public function test_design_page_shows_add_to_cart_button_for_authenticated(): void
    {
        $user = User::factory()->create();
        $design = Design::factory()->published()->create(['title' => 'Buyable Design']);
        DesignProductMapping::factory()->create(['design_id' => $design->id]);

        $this->actingAs($user)
            ->get('/designs/'.$design->id)
            ->assertOk()
            ->assertSee('Add to cart');
    }
}
