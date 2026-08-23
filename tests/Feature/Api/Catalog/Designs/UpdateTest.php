<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Designs;

use App\Models\Design;
use App\Models\User;
use Database\Factories\DesignerProfileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_update_design(): void
    {
        $owner = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfileFactory::new()->for($owner, 'user')->create();
        $design = Design::factory()->for($profile, 'designer')->create([
            'title' => 'Original',
        ]);

        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->patchJson('/api/me/designs/'.$design->id, ['title' => 'Hijacked'])
            ->assertForbidden();

        $this->assertSame('Original', $design->fresh()->title);
    }

    public function test_designer_can_update_own_design(): void
    {
        $owner = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfileFactory::new()->for($owner, 'user')->create();
        $design = Design::factory()->for($profile, 'designer')->create([
            'title' => 'Original',
            'status' => 'draft',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->patchJson('/api/me/designs/'.$design->id, [
                'title' => 'Updated',
                'status' => 'published',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated')
            ->assertJsonPath('data.status', 'published');

        $this->assertSame('Updated', $design->fresh()->title);
        $this->assertSame('published', $design->fresh()->status);
    }

    public function test_designer_cannot_update_others_design(): void
    {
        $owner = User::factory()->create(['role' => 'designer']);
        $ownerProfile = DesignerProfileFactory::new()->for($owner, 'user')->create();
        $design = Design::factory()->for($ownerProfile, 'designer')->create([
            'title' => 'Belongs to owner',
        ]);

        $intruder = User::factory()->create(['role' => 'designer']);
        DesignerProfileFactory::new()->for($intruder, 'user')->create();

        $this->actingAs($intruder, 'sanctum')
            ->patchJson('/api/me/designs/'.$design->id, ['title' => 'Stolen'])
            ->assertForbidden();

        $this->assertSame('Belongs to owner', $design->fresh()->title);
    }
}
