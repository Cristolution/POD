<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Designs;

use App\Models\Design;
use App\Models\User;
use Database\Factories\DesignerProfileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_delete_design(): void
    {
        $owner = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfileFactory::new()->for($owner, 'user')->create();
        $design = Design::factory()->for($profile, 'designer')->create();

        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->deleteJson('/api/me/designs/'.$design->id)
            ->assertForbidden();

        $this->assertDatabaseHas('designs', ['id' => $design->id]);
    }

    public function test_designer_can_delete_own_design(): void
    {
        $owner = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfileFactory::new()->for($owner, 'user')->create();
        $design = Design::factory()->for($profile, 'designer')->create();

        $this->actingAs($owner, 'sanctum')
            ->deleteJson('/api/me/designs/'.$design->id)
            ->assertNoContent();

        // Soft delete: row remains but deleted_at is set.
        $this->assertSoftDeleted('designs', ['id' => $design->id]);
    }

    public function test_designer_cannot_delete_others_design(): void
    {
        $owner = User::factory()->create(['role' => 'designer']);
        $ownerProfile = DesignerProfileFactory::new()->for($owner, 'user')->create();
        $design = Design::factory()->for($ownerProfile, 'designer')->create();

        $intruder = User::factory()->create(['role' => 'designer']);
        DesignerProfileFactory::new()->for($intruder, 'user')->create();

        $this->actingAs($intruder, 'sanctum')
            ->deleteJson('/api/me/designs/'.$design->id)
            ->assertForbidden();

        $this->assertDatabaseHas('designs', ['id' => $design->id, 'deleted_at' => null]);
    }
}
