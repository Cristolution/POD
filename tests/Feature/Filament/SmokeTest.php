<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Pages\KpiDashboard;
use App\Filament\Pages\Reports\RevenueByDayReport;
use App\Filament\Resources\Designs\Pages\EditDesign;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Design;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 4 smoke tests — exercise the critical admin user paths via HTTP and
 * Livewire. Replaces the plan's manual browser checks, which cannot run via
 * `php artisan serve` on Windows + PHP 8.5 (see Task 1).
 *
 * Coverage:
 *  - Admin can reach the KPI dashboard at /admin
 *  - Admin can reach a representative report page (revenue by day)
 *  - Admin can mount the edit screen for a design
 *  - Admin can mount the view screen for an order
 *  - Non-admin (customer) is forbidden on the same routes
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reach_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_admin_can_mount_kpi_dashboard_as_livewire_component(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(KpiDashboard::class)
            ->assertSuccessful();
    }

    public function test_admin_can_edit_design(): void
    {
        $design = Design::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(EditDesign::class, ['record' => $design->getKey()])
            ->assertSuccessful();
    }

    public function test_admin_can_view_order(): void
    {
        $order = Order::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ViewOrder::class, ['record' => $order->getKey()])
            ->assertSuccessful();
    }

    public function test_admin_can_reach_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/revenue-by-day-report')
            ->assertOk();
    }

    public function test_admin_can_mount_report_as_livewire_component(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(RevenueByDayReport::class)
            ->assertSuccessful();
    }

    public function test_non_admin_gets_forbidden_on_admin_routes(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get('/admin')->assertForbidden();
        $this->actingAs($customer)->get('/admin/revenue-by-day-report')->assertForbidden();
    }
}
