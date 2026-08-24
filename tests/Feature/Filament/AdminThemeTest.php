<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_dashboard_renders_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_themed_css_file_is_published(): void
    {
        $this->assertFileExists(base_path('resources/css/filament/admin/theme.css'));
    }

    public function test_themed_css_contains_sand_coral_palette(): void
    {
        $css = file_get_contents(base_path('resources/css/filament/admin/theme.css'));

        $this->assertStringContainsString('#FF6B4A', $css);
        $this->assertStringContainsString('#F5EFE6', $css);
        $this->assertStringContainsString('--color-primary-500', $css);
        $this->assertStringContainsString('--color-gray-100', $css);
        $this->assertStringContainsString('border-radius: 0', $css);
    }

    public function test_panel_provider_references_brutalist_theme(): void
    {
        $provider = file_get_contents(base_path('app/Providers/Filament/AdminPanelProvider.php'));

        $this->assertStringContainsString("viteTheme('resources/css/filament/admin/theme.css')", $provider);
        $this->assertStringContainsString("->brandName('POD/ Admin')", $provider);
        $this->assertStringContainsString('EnsureAdmin::class', $provider);
        $this->assertStringContainsString("Color::hex('#FF6B4A')", $provider);
    }
}
