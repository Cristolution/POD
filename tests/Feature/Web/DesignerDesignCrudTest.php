<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\Media;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DesignerDesignCrudTest extends TestCase
{
    use RefreshDatabase;

    private function designerUser(): array
    {
        $user = User::factory()->designer()->create();
        $profile = DesignerProfile::factory()->for($user)->create();

        return [$user, $profile];
    }

    private function fakeImage(string $name = 'mockup.png'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 600, 600);
    }

    // ------------------------------------------------------------------
    // Create
    // ------------------------------------------------------------------

    public function test_get_create_form_renders_for_designer(): void
    {
        [$user] = $this->designerUser();
        Category::factory()->create(['name' => 'T-shirts']);

        $this->actingAs($user)
            ->get(route('designer.designs.create'))
            ->assertOk()
            ->assertSee('Create design')
            ->assertSee('Mockup image')
            ->assertSee('Print-ready file')
            ->assertSee('T-shirts');
    }

    public function test_get_create_form_redirects_customers_to_login(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('designer.designs.create'))
            ->assertForbidden();
    }

    public function test_post_create_with_two_image_files_creates_design_with_two_media(): void
    {
        Storage::fake('public');
        [$user] = $this->designerUser();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('designer.designs.store'), [
            'category_id' => $category->id,
            'title' => 'My First Design',
            'status' => 'draft',
            'mockup' => $this->fakeImage('mockup.png'),
            'print_file' => $this->fakeImage('print.png'),
        ]);

        $design = Design::where('title', 'My First Design')->first();
        $this->assertNotNull($design);
        $this->assertSame($user->id, $design->designer->user_id);

        $this->assertSame(2, Media::query()->where('model_id', (string) $design->id)->count());
        $this->assertNotNull(Media::query()->where('model_id', (string) $design->id)->where('collection_name', 'mockup')->first());
        $this->assertNotNull(Media::query()->where('model_id', (string) $design->id)->where('collection_name', 'print_file')->first());

        $response->assertRedirect(route('designer.designs.show', $design));
    }

    public function test_post_create_without_files_rejects_validation(): void
    {
        [$user] = $this->designerUser();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->from(route('designer.designs.create'))
            ->post(route('designer.designs.store'), [
                'category_id' => $category->id,
                'title' => 'No Files',
            ])
            ->assertRedirect(route('designer.designs.create'))
            ->assertSessionHasErrors(['mockup', 'print_file']);
    }

    public function test_post_create_with_non_image_rejects_validation(): void
    {
        [$user] = $this->designerUser();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->from(route('designer.designs.create'))
            ->post(route('designer.designs.store'), [
                'category_id' => $category->id,
                'title' => 'Bad MIME',
                'mockup' => UploadedFile::fake()->create('doc.pdf', 100),
                'print_file' => $this->fakeImage('print.png'),
            ])
            ->assertRedirect(route('designer.designs.create'))
            ->assertSessionHasErrors('mockup');
    }

    // ------------------------------------------------------------------
    // Show
    // ------------------------------------------------------------------

    public function test_owner_can_view_design(): void
    {
        [$user, $profile] = $this->designerUser();
        $design = Design::factory()->forDesigner($profile)->create(['title' => 'Viewable']);

        $this->actingAs($user)
            ->get(route('designer.designs.show', $design))
            ->assertOk()
            ->assertSee('Viewable');
    }

    public function test_other_designer_cannot_view_design(): void
    {
        [$owner, $profile] = $this->designerUser();
        $design = Design::factory()->forDesigner($profile)->create();

        $other = User::factory()->designer()->create();
        DesignerProfile::factory()->for($other)->create();

        $this->actingAs($other)
            ->get(route('designer.designs.show', $design))
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Edit / Update
    // ------------------------------------------------------------------

    public function test_owner_can_open_edit_form(): void
    {
        [$user, $profile] = $this->designerUser();
        $design = Design::factory()->forDesigner($profile)->create(['title' => 'Editable']);

        $this->actingAs($user)
            ->get(route('designer.designs.edit', $design))
            ->assertOk()
            ->assertSee('Editable');
    }

    public function test_other_designer_cannot_open_edit_form(): void
    {
        [$owner, $profile] = $this->designerUser();
        $design = Design::factory()->forDesigner($profile)->create();

        $other = User::factory()->designer()->create();
        DesignerProfile::factory()->for($other)->create();

        $this->actingAs($other)
            ->get(route('designer.designs.edit', $design))
            ->assertForbidden();
    }

    public function test_owner_can_update_title(): void
    {
        [$user, $profile] = $this->designerUser();
        $design = Design::factory()->forDesigner($profile)->create(['title' => 'Before']);

        $this->actingAs($user)
            ->patch(route('designer.designs.update', $design), [
                'title' => 'After',
                'category_id' => $design->category_id,
                'status' => 'published',
            ])
            ->assertRedirect(route('designer.designs.show', $design));

        $design->refresh();
        $this->assertSame('After', $design->title);
        $this->assertSame('published', $design->status);
    }

    public function test_updating_mockup_replaces_old_media_row(): void
    {
        Storage::fake('public');
        [$user, $profile] = $this->designerUser();
        $design = Design::factory()->forDesigner($profile)->create();

        // Create an existing mockup via the API path so we have something to replace.
        $existing = Media::create([
            'model_type' => $design->getMorphClass(),
            'model_id' => (string) $design->id,
            'collection_name' => 'mockup',
            'file_path' => 'media/mockup/old.png',
        ]);

        $this->actingAs($user)
            ->patch(route('designer.designs.update', $design), [
                'title' => $design->title,
                'category_id' => $design->category_id,
                'status' => $design->status,
                'mockup' => $this->fakeImage('new-mockup.png'),
            ])
            ->assertRedirect(route('designer.designs.show', $design));

        $this->assertNull(Media::query()->find($existing->id), 'Old mockup row should be gone.');
        $this->assertSame(1, Media::query()->where('model_id', (string) $design->id)->where('collection_name', 'mockup')->count());
    }

    // ------------------------------------------------------------------
    // Destroy
    // ------------------------------------------------------------------

    public function test_owner_can_delete_design(): void
    {
        [$user, $profile] = $this->designerUser();
        $design = Design::factory()->forDesigner($profile)->create();

        $this->actingAs($user)
            ->delete(route('designer.designs.destroy', $design))
            ->assertRedirect(route('designer.dashboard'));

        $this->assertNotNull(Design::withTrashed()->find($design->id)->deleted_at);
    }

    public function test_other_designer_cannot_delete_design(): void
    {
        [$owner, $profile] = $this->designerUser();
        $design = Design::factory()->forDesigner($profile)->create();

        $other = User::factory()->designer()->create();
        DesignerProfile::factory()->for($other)->create();

        $this->actingAs($other)
            ->delete(route('designer.designs.destroy', $design))
            ->assertForbidden();

        $this->assertNull(Design::find($design->id)->deleted_at);
    }

    // ------------------------------------------------------------------
    // Inline mappings card on the show page
    // ------------------------------------------------------------------

    public function test_show_page_lists_mappings_with_edit_and_add_links(): void
    {
        [$user, $profile] = $this->designerUser();
        $design = Design::factory()->forDesigner($profile)->create(['title' => 'My Map Host']);

        $template = ProductTemplate::factory()->create(['name' => 'Classic Tee']);
        $printer = PrinterProviderProfile::factory()->create();
        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
            'final_price' => 25.00,
        ]);

        $response = $this->actingAs($user)
            ->get(route('designer.designs.show', $design));

        $response->assertOk()
            ->assertSee('Classic Tee')
            ->assertSee(route('designer.mappings.create', ['design_id' => $design->id]), false)
            ->assertSee(route('designer.mappings.edit', $mapping), false)
            ->assertSee(route('designer.mappings.destroy', $mapping), false);
    }
}
