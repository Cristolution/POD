<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Media;

use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_any_media(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $design = Design::factory()->create();
        $media = Media::factory()->create([
            'model_type' => Design::class,
            'model_id' => $design->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/media/{$media->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_user_cannot_delete_unrelated_media(): void
    {
        $ownerDesigner = User::factory()->create(['role' => 'designer']);
        $ownerProfile = DesignerProfile::factory()->create(['user_id' => $ownerDesigner->id]);
        $design = Design::factory()->for($ownerProfile, 'designer')->create();
        $media = Media::factory()->create([
            'model_type' => Design::class,
            'model_id' => $design->id,
        ]);

        $stranger = User::factory()->create(['role' => 'designer']);

        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/media/{$media->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_owner_can_delete_own_media(): void
    {
        $ownerUser = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfile::factory()->create(['user_id' => $ownerUser->id]);
        $design = Design::factory()->for($profile, 'designer')->create();
        $media = Media::factory()->create([
            'model_type' => Design::class,
            'model_id' => $design->id,
        ]);

        $this->actingAs($ownerUser, 'sanctum')
            ->deleteJson("/api/media/{$media->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }
}
