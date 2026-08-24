<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_for_anonymous(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Print anything')
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
}
