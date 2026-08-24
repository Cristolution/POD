<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TagResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_tags(): void
    {
        Tag::factory()->count(5)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListTags::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_tag(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateTag::class)
            ->fillForm(['name' => 'minimal'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tags', ['name' => 'minimal']);
    }
}
