<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignerDashboardTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Public profile
    // ------------------------------------------------------------------

    public function test_public_designer_profile_is_reachable_and_shows_bio(): void
    {
        $designer = DesignerProfile::factory()
            ->for(User::factory()->designer()->create(['name' => 'Ada Lovelace']))
            ->create(['bio' => 'I draw cyberpunk florals.']);

        $design = Design::factory()
            ->forDesigner($designer)
            ->published()
            ->for(Category::factory()->create(['name' => 'Posters']))
            ->create(['title' => 'Neon Bloom']);

        $this->get(route('designer.show', $designer))
            ->assertOk()
            ->assertSee('Ada Lovelace')
            ->assertSee('I draw cyberpunk florals.')
            ->assertSee('Neon Bloom')
            ->assertSee('Published designs');
    }

    public function test_public_designer_profile_404_for_unknown(): void
    {
        $this->get('/designers/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    public function test_public_designer_profile_only_lists_published_designs(): void
    {
        $designer = DesignerProfile::factory()->create();

        Design::factory()->forDesigner($designer)->published()->create(['title' => 'Visible']);
        Design::factory()->forDesigner($designer)->draft()->create(['title' => 'Hidden']);

        $this->get(route('designer.show', $designer))
            ->assertOk()
            ->assertSee('Visible')
            ->assertDontSee('Hidden');
    }

    // ------------------------------------------------------------------
    // Designer dashboard (auth + role:designer)
    // ------------------------------------------------------------------

    public function test_dashboard_redirects_anonymous_to_login(): void
    {
        $this->get(route('designer.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_returns_403_for_customer(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('designer.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_returns_200_for_designer(): void
    {
        $user = User::factory()->designer()->create(['name' => 'Grace Hopper']);
        $profile = DesignerProfile::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('designer.dashboard'))
            ->assertOk()
            ->assertSee('Designer')
            ->assertSee('Grace Hopper')
            ->assertSee('Edit profile')
            ->assertSee('Your designs');
    }

    public function test_dashboard_lists_all_own_designs_in_any_status(): void
    {
        $user = User::factory()->designer()->create();
        $profile = DesignerProfile::factory()->for($user)->create();

        Design::factory()->forDesigner($profile)->published()->create(['title' => 'Pub Design']);
        Design::factory()->forDesigner($profile)->draft()->create(['title' => 'Draft Design']);
        Design::factory()->forDesigner($profile)->archived()->create(['title' => 'Old Design']);

        // Other designer's work — must not appear.
        $other = DesignerProfile::factory()->create();
        Design::factory()->forDesigner($other)->published()->create(['title' => 'Other Designer']);

        $this->actingAs($user)
            ->get(route('designer.dashboard'))
            ->assertOk()
            ->assertSee('Pub Design')
            ->assertSee('Draft Design')
            ->assertSee('Old Design')
            ->assertSee('published')
            ->assertSee('draft')
            ->assertSee('archived')
            ->assertDontSee('Other Designer');
    }

    public function test_dashboard_redirects_to_login_when_role_middleware_fails_unauthenticated(): void
    {
        $this->get(route('designer.edit'))->assertRedirect(route('login'));
    }

    // ------------------------------------------------------------------
    // Designer edit
    // ------------------------------------------------------------------

    public function test_edit_page_renders_form_for_designer(): void
    {
        $user = User::factory()->designer()->create();
        DesignerProfile::factory()->for($user)->create(['bio' => 'Old bio']);

        $this->actingAs($user)
            ->get(route('designer.edit'))
            ->assertOk()
            ->assertSee('Edit profile')
            ->assertSee('Old bio');
    }

    public function test_edit_page_403_for_non_designer(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('designer.edit'))
            ->assertForbidden();
    }

    public function test_update_profile_with_valid_data_persists_bio(): void
    {
        $user = User::factory()->designer()->create();
        $profile = DesignerProfile::factory()->for($user)->create(['bio' => 'Old']);

        $this->actingAs($user)
            ->patch(route('designer.update'), [
                'bio' => 'New bio with extra flair.',
            ])
            ->assertRedirect(route('designer.edit'))
            ->assertSessionHas('status', 'Profile updated.');

        $profile->refresh();
        $this->assertSame('New bio with extra flair.', $profile->bio);
    }

    public function test_update_profile_with_too_long_bio_fails_validation(): void
    {
        $user = User::factory()->designer()->create();
        $profile = DesignerProfile::factory()->for($user)->create(['bio' => 'Original']);

        $this->actingAs($user)
            ->from(route('designer.edit'))
            ->patch(route('designer.update'), [
                'bio' => str_repeat('a', 2001),
            ])
            ->assertRedirect(route('designer.edit'))
            ->assertSessionHasErrors('bio');

        $profile->refresh();
        $this->assertSame('Original', $profile->bio);
    }

    public function test_update_profile_allows_null_bio(): void
    {
        $user = User::factory()->designer()->create();
        $profile = DesignerProfile::factory()->for($user)->create(['bio' => 'Will be cleared']);

        $this->actingAs($user)
            ->patch(route('designer.update'), [
                'bio' => null,
            ])
            ->assertRedirect(route('designer.edit'))
            ->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertNull($profile->bio);
    }
}
