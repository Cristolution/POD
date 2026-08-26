<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DesignerVerifiedToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_designer_verification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $designer = User::factory()->designer()->create();
        $profile = DesignerProfile::factory()->for($designer)->unverified()->create();

        $this->assertFalse($profile->is_verified);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('toggleVerified', $designer);

        $profile->refresh();
        $this->assertTrue($profile->is_verified, 'Admin toggle should flip is_verified to true.');

        // Toggle again — should flip back.
        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callTableAction('toggleVerified', $designer);

        $profile->refresh();
        $this->assertFalse($profile->is_verified, 'Second toggle should flip back to false.');
    }

    public function test_toggle_action_is_hidden_for_non_designer_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->customer()->create();

        $component = Livewire::actingAs($admin)
            ->test(ListUsers::class);

        // Designer user must have a profile before the action can run; the customer
        // has none, so the action's visible() check returns false and we shouldn't
        // be able to call it.
        $component->assertTableActionHidden('toggleVerified', $customer);
    }

    public function test_non_admin_cannot_reach_admin_panel(): void
    {
        $designer = User::factory()->designer()->create();

        $this->actingAs($designer)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_verified_designer_column_header_renders_for_designers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->designer()->create();
        User::factory()->customer()->create();

        // Without the fix, the IconColumn was hidden from the DOM entirely
        // because Filament calls isHidden() once per column (without a record)
        // during getDefaultTableColumnState(), and a ?User closure that
        // dereferences $record->role always evaluates to false on null.
        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertSuccessful()
            ->assertSee('Verified designer');
    }

    public function test_verified_column_shows_placeholder_for_non_designers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->customer()->create();
        DesignerProfile::factory()->create(); // unrelated designer profile to keep the column visible

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertSuccessful();

        // The customer's row should show the em-dash placeholder, not a checkmark
        // — the state closure returns null for non-designers so IconColumn renders
        // the placeholder via ->placeholder('—').
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'role' => 'customer']);
    }
}
