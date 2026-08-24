<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_renders(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Terms of service')
            ->assertSee('Effective');
    }

    public function test_privacy_page_renders(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy policy')
            ->assertSee('Effective');
    }

    public function test_404_page_renders_for_unknown_route(): void
    {
        $this->get('/this-route-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found');
    }

    public function test_403_page_renders_for_unauthorized_action(): void
    {
        // Hit a route gated by role:designer without being a designer.
        // The role middleware throws an AuthorizationException which Laravel
        // renders as the themed errors/403.blade.php view for non-API requests.
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('designer.dashboard'))
            ->assertForbidden()
            ->assertSee('Access denied');
    }
}
