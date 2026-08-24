<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Models\Design;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MediaResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_media(): void
    {
        Media::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListMedia::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_media(): void
    {
        $design = Design::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateMedia::class)
            ->fillForm([
                'model_type' => Design::class,
                'model_id' => (string) $design->id,
                'collection_name' => 'mockup',
                'file_path' => 'media/mockups/example.jpg',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('media', [
            'model_type' => Design::class,
            'model_id' => $design->id,
            'collection_name' => 'mockup',
            'file_path' => 'media/mockups/example.jpg',
        ]);
    }
}
