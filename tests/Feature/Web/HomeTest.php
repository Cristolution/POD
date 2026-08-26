<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_for_anonymous(): void
    {
        // "Print" and "anything." are split across <br> in the hero, so we
        // assert each word separately rather than the contiguous phrase.
        $this->get('/')
            ->assertOk()
            ->assertSee('Print')
            ->assertSee('anything')
            ->assertSee('POD'); // brand mark
    }

    public function test_home_page_shows_login_link_when_logged_out(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Login');
    }

    public function test_home_page_shows_user_name_when_logged_in(): void
    {
        $user = User::factory()->create(['name' => 'Cris Tester']);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Cris Tester');
    }

    public function test_home_page_lists_published_designs_and_skips_drafts(): void
    {
        Design::factory()->create(['title' => 'Cosmic Cat', 'status' => 'published']);
        Design::factory()->create(['title' => 'Plain Puppy', 'status' => 'draft']);
        Design::factory()->create(['title' => 'Hidden Hawk', 'status' => 'archived']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Cosmic Cat')
            ->assertDontSee('Plain Puppy')
            ->assertDontSee('Hidden Hawk');
    }

    public function test_home_page_caps_featured_designs_at_six(): void
    {
        // 8 published designs — only the 6 most recent should show on the
        // homepage hero strip. Older ones are filtered server-side.
        for ($i = 0; $i < 8; $i++) {
            Design::factory()->create([
                'title' => 'Design '.$i,
                'status' => 'published',
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->get('/')->assertOk();

        $response->assertSee('Design 0');
        $response->assertSee('Design 5');
        $response->assertDontSee('Design 6');
        $response->assertDontSee('Design 7');
    }

    public function test_home_page_lists_root_categories(): void
    {
        $root = Category::factory()->create(['name' => 'Outerwear', 'parent_id' => null]);
        Category::factory()->create(['name' => 'Child Tee', 'parent_id' => $root->id]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Outerwear')
            ->assertDontSee('Child Tee');
    }

    public function test_home_page_lists_designers_with_published_work(): void
    {
        $activeUser = User::factory()->create(['name' => 'Avery Active']);
        $activeDesigner = DesignerProfile::factory()->create(['user_id' => $activeUser->id]);
        Design::factory()->create([
            'designer_id' => $activeDesigner->id,
            'status' => 'published',
        ]);

        $idleUser = User::factory()->create(['name' => 'Idle Idle']);
        DesignerProfile::factory()->create(['user_id' => $idleUser->id]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Avery Active')
            ->assertDontSee('Idle Idle');
    }

    public function test_home_page_renders_clean_with_no_data(): void
    {
        // No designs, categories, or designers seeded — every section must
        // still render 200. This guards against N+1 explosions and division-
        // by-zero style crashes when the platform is empty.
        $this->get('/')
            ->assertOk()
            ->assertSee('Print')
            ->assertSee('anything');
    }
}
