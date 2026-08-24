<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Designs\Pages\CreateDesign;
use App\Filament\Resources\Designs\Pages\ListDesigns;
use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DesignResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_designs(): void
    {
        Design::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListDesigns::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_design(): void
    {
        $designer = DesignerProfile::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateDesign::class)
            ->fillForm([
                'designer_id' => (string) $designer->id,
                'category_id' => (string) $category->id,
                'title' => 'Sunset Mountains',
                'status' => 'published',
                'tags' => [(string) $tag->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('designs', [
            'title' => 'Sunset Mountains',
            'status' => 'published',
        ]);

        $designId = Design::where('title', 'Sunset Mountains')->firstOrFail()->id;
        $this->assertDatabaseHas('design_tag', [
            'design_id' => $designId,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_admin_can_publish_draft_design(): void
    {
        $design = Design::factory()->create(['status' => 'draft']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertSame('draft', $design->fresh()->status);

        $design->update(['status' => 'published']);

        $this->assertSame('published', $design->fresh()->status);
    }
}
