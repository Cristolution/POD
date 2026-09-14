<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Approvals;

use App\Filament\Pages\Approvals\ApprovalsHub;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalsHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_approvals_hub(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ApprovalsHub::class)
            ->assertSuccessful()
            ->assertSee('Approvals')
            ->assertSee('Pending payments')
            ->assertSee('Pending orders')
            ->assertSee('Designers to verify');
    }

    public function test_hub_counts_match_pending_queue_totals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 2 pending payments, 1 confirmed (excluded), 1 rejected (excluded).
        Payment::factory()->count(2)->create(['status' => 'pending']);
        Payment::factory()->confirmed($admin)->create();
        Payment::factory()->rejected()->create();

        // 2 pending + 1 paid = 3 explicitly-open orders, plus 2 terminal.
        Order::factory()->count(2)->create(['status' => 'pending']);
        Order::factory()->count(1)->create(['status' => 'paid']);
        Order::factory()->count(1)->create(['status' => 'delivered']);
        Order::factory()->count(1)->create(['status' => 'cancelled']);

        // 2 designers without a verified profile.
        User::factory()->designer()->create(); // unverified (no profile)
        User::factory()->designer()->create(); // unverified (no profile)

        $expectedOpenOrders = Order::query()
            ->whereIn('status', ['pending', 'paid', 'processing', 'shipped'])
            ->count();

        Livewire::actingAs($admin)
            ->test(ApprovalsHub::class)
            ->assertSuccessful()
            ->assertSee((string) 2)  // pending payments
            ->assertSee((string) $expectedOpenOrders)  // pending orders
            ->assertSee((string) 2); // unverified designers

        $this->assertSame(2, Payment::query()->where('status', 'pending')->count());
    }

    public function test_hub_renders_links_to_each_subpage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ApprovalsHub::class)
            ->assertSuccessful()
            ->assertSee('/admin/approvals-payments', false)
            ->assertSee('/admin/approvals-orders', false)
            ->assertSee('/admin/approvals-designers', false);
    }

    public function test_non_admin_cannot_reach_approvals_hub(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get('/admin/approvals-hub')
            ->assertForbidden();
    }
}
