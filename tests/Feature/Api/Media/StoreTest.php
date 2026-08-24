<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Media;

use App\Models\Design;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_media(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'designer']);
        $design = Design::factory()->create();

        $file = UploadedFile::fake()->image('mockup.png', 800, 600);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/media', [
                'file' => $file,
                'model_type' => 'design',
                'model_id' => $design->id,
                'collection_name' => 'mockup',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.collection_name', 'mockup')
            ->assertJsonPath('data.model_type', Design::class)
            ->assertJsonPath('data.model_id', $design->id)
            ->assertJsonStructure(['data' => ['url', 'file_path']]);

        $this->assertDatabaseHas('media', [
            'model_type' => Design::class,
            'model_id' => $design->id,
            'collection_name' => 'mockup',
        ]);

        Storage::disk('public')->assertExists($response->json('data.file_path'));
    }

    public function test_anonymous_is_rejected(): void
    {
        $design = Design::factory()->create();
        $file = UploadedFile::fake()->image('mockup.png');

        $this->postJson('/api/media', [
            'file' => $file,
            'model_type' => 'design',
            'model_id' => $design->id,
            'collection_name' => 'mockup',
        ])->assertUnauthorized();
    }

    public function test_invalid_file_type_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'designer']);
        $design = Design::factory()->create();

        // fake()->create() creates a generic file type not in our mimes whitelist.
        $bad = UploadedFile::fake()->create('document.pdf', 50, 'application/pdf');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/media', [
                'file' => $bad,
                'model_type' => 'design',
                'model_id' => $design->id,
                'collection_name' => 'mockup',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_owner_can_be_resolved_from_polymorphic_alias(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'customer']);
        $design = Design::factory()->create();
        $file = UploadedFile::fake()->image('attachment.png');

        // The controller should map `design` -> App\Models\Design::class.
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/media', [
                'file' => $file,
                'model_type' => 'design',
                'model_id' => $design->id,
                'collection_name' => 'attachment',
            ])
            ->assertCreated();

        $media = Media::query()->first();
        $this->assertNotNull($media);
        $this->assertSame('attachment', $media->collection_name);
        $this->assertSame($design->id, $media->model_id);
    }
}
