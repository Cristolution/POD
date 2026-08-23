<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;
use Tests\TestCase;

class DocsProductionGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_swagger_routes_are_public_in_local(): void
    {
        // Default env is 'local' / 'testing' — these routes should be reachable.
        $this->get('/api/docs')->assertOk();
        $this->get('/api/docs.json')->assertOk();
    }

    public function test_swagger_routes_require_admin_in_production(): void
    {
        // Simulate production for the assertion only.
        $this->app->detectEnvironment(fn () => 'production');

        // Route registration happened at boot under the previous env. Reset
        // the route collection and re-register both the l5-swagger package's
        // own routes (required by SwaggerController@api internally) and our
        // own routes/web.php (which now takes the production branch).
        $router = $this->app->make('router');
        $router->setRoutes(new RouteCollection);
        require base_path('vendor/darkaonline/l5-swagger/src/routes.php');
        require base_path('routes/web.php');

        // Force JSON expectation on every request — without this the `auth`
        // middleware's redirectTo() calls route('login'), which would throw
        // RouteNotFoundException because the login route isn't registered
        // until Phase 3. With Accept: application/json the middleware throws
        // an AuthenticationException that renders as a clean 401.
        $jsonHeaders = ['Accept' => 'application/json'];

        // Anonymous request is rejected. Accept either a redirect (when the
        // `login` named route is registered) or a 401 (when it isn't yet).
        $r1 = $this->get('/api/docs', $jsonHeaders);
        $this->assertTrue(
            $r1->isRedirect() || $r1->status() === 401,
            "Expected redirect or 401 for unauthenticated /api/docs, got {$r1->status()}"
        );

        $r2 = $this->get('/api/docs.json', $jsonHeaders);
        $this->assertTrue(
            $r2->isRedirect() || $r2->status() === 401,
            "Expected redirect or 401 for unauthenticated /api/docs.json, got {$r2->status()}"
        );

        // A non-admin user is also rejected.
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)
            ->get('/api/docs', $jsonHeaders)
            ->assertForbidden();
        $this->actingAs($customer)
            ->get('/api/docs.json', $jsonHeaders)
            ->assertForbidden();

        // An admin passes both.
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/api/docs', $jsonHeaders)->assertOk();
        $this->actingAs($admin)->get('/api/docs.json', $jsonHeaders)->assertOk();
    }
}
