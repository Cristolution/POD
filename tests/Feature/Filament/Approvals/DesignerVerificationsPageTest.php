<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Approvals;

use App\Filament\Pages\Approvals\DesignerVerificationsPage;
use App\Models\DesignerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DesignerVerificationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_designer_verifications_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $designer = User::factory()->designer()->create();
        DesignerProfile::factory()->for($designer)->unverified()->create();

        Livewire::actingAs($admin)
            ->test(DesignerVerificationsPage::class)
            ->assertSuccessful()
            ->assertSee($designer->name)
            ->assertSee($designer->email);
    }

    public function test_only_unverified_or_profileless_designers_appear(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $unverifiedDesigner = User::factory()->designer()->create();
        DesignerProfile::factory()->for($unverifiedDesigner)->unverified()->create();

        User::factory()->designer()->create(); // profileless — included in queue

        $verifiedDesigner = User::factory()->designer()->create();
        DesignerProfile::factory()->for($verifiedDesigner)->create(['is_verified' => true]);

        User::factory()->customer()->create();
        User::factory()->admin()->create();

        $component = Livewire::actingAs($admin)->test(DesignerVerificationsPage::class);

        $this->assertCount(2, $component->get('designers'));
    }

    public function test_admin_can_verify_unverified_designer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $designer = User::factory()->designer()->create();
        $profile = DesignerProfile::factory()->for($designer)->unverified()->create();

        Livewire::actingAs($admin)
            ->test(DesignerVerificationsPage::class)
            ->call('verifyDesigner', $designer->id);

        $this->assertTrue($profile->fresh()->is_verified);
    }

    public function test_queue_refreshes_after_verification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $designer = User::factory()->designer()->create();
        DesignerProfile::factory()->for($designer)->unverified()->create();

        $component = Livewire::actingAs($admin)->test(DesignerVerificationsPage::class);
        $this->assertCount(1, $component->get('designers'));

        $component->call('verifyDesigner', $designer->id);

        $this->assertCount(0, $component->get('designers'));
    }

    public function test_verify_on_profileless_designer_does_not_throw(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $designer = User::factory()->designer()->create(); // no profile

        Livewire::actingAs($admin)
            ->test(DesignerVerificationsPage::class)
            ->call('verifyDesigner', $designer->id)
            ->assertHasNoErrors();

        $this->assertNull($designer->fresh()->designerProfile);
    }

    public function test_non_admin_cannot_reach_designer_verifications(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get('/admin/approvals-designers')
            ->assertForbidden();
    }
}
