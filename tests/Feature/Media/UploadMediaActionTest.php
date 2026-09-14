<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Actions\Media\UploadMediaAction;
use App\Models\Design;
use App\Models\DesignProductMapping;
use App\Models\Media;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Per-product mockup upload must NOT leave a "ghost" preview — a Media
 * row referencing product_template_id without a corresponding
 * DesignProductMapping means the customer sees a per-product preview
 * for a product the design isn't actually purchasable on.
 *
 * These tests pin the integrity rule:
 *   collection_name='mockup' + product_template_id != null
 *     ↔ DesignProductMapping(design_id, product_template_id) exists
 */
class UploadMediaActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function per_product_mockup_upload_auto_creates_missing_mapping(): void
    {
        Storage::fake('public');

        $user = User::factory()->designer()->create();
        $profile = \App\Models\DesignerProfile::factory()->create(['user_id' => $user->id]);
        $design = Design::factory()
            ->for($profile, 'designer')
            ->published()
            ->create();
        $printer = PrinterProviderProfile::factory()->create();
        $product = ProductTemplate::factory()
            ->for($printer, 'printerProvider')
            ->create(['type' => 'mug', 'base_cost' => 12.50]);

        // Pre-condition: no DesignProductMapping exists for this pair.
        $this->assertDatabaseMissing('design_product_mappings', [
            'design_id' => $design->id,
            'product_template_id' => $product->id,
        ]);

        // Upload a per-product mockup via the action.
        $file = UploadedFile::fake()->image('mockup.jpg');
        $media = app(UploadMediaAction::class)->execute(
            $file, $design, 'mockup', $product->id,
        );

        // Media is created with the per-product mockup fields.
        $this->assertSame('mockup', $media->collection_name);
        $this->assertSame($product->id, $media->product_template_id);

        // Integrity invariant: mapping now exists for this (design, template).
        $this->assertDatabaseHas('design_product_mappings', [
            'design_id' => $design->id,
            'product_template_id' => $product->id,
            'preferred_printer_id' => $product->printer_provider_id,
        ]);
    }

    #[Test]
    public function per_product_mockup_upload_does_not_duplicate_existing_mapping(): void
    {
        Storage::fake('public');

        $user = User::factory()->designer()->create();
        $profile = \App\Models\DesignerProfile::factory()->create(['user_id' => $user->id]);
        $design = Design::factory()
            ->for($profile, 'designer')
            ->published()
            ->create();
        $product = ProductTemplate::factory()
            ->for(PrinterProviderProfile::factory(), 'printerProvider')
            ->create(['type' => 'mug', 'base_cost' => 12.50]);

        // Mapping already exists.
        DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $product->id,
            'preferred_printer_id' => $product->printer_provider_id,
            'final_price' => 27.50,
        ]);

        $file = UploadedFile::fake()->image('mockup.jpg');
        app(UploadMediaAction::class)->execute(
            $file, $design, 'mockup', $product->id,
        );

        // No duplicate — count remains 1, price untouched.
        $this->assertSame(1, DesignProductMapping::where([
            'design_id' => $design->id,
            'product_template_id' => $product->id,
        ])->count());
        $this->assertDatabaseHas('design_product_mappings', [
            'design_id' => $design->id,
            'product_template_id' => $product->id,
            'final_price' => '27.50',
        ]);
    }

    #[Test]
    public function default_mockup_upload_does_not_touch_mappings(): void
    {
        Storage::fake('public');

        $user = User::factory()->designer()->create();
        $profile = \App\Models\DesignerProfile::factory()->create(['user_id' => $user->id]);
        $design = Design::factory()
            ->for($profile, 'designer')
            ->published()
            ->create();

        // Upload a DEFAULT mockup (product_template_id = null).
        $file = UploadedFile::fake()->image('mockup.jpg');
        app(UploadMediaAction::class)->execute(
            $file, $design, 'mockup', null,
        );

        // No per-product mapping should be auto-created.
        $this->assertSame(0, DesignProductMapping::count());
    }

    #[Test]
    public function print_file_upload_does_not_touch_mappings(): void
    {
        Storage::fake('public');

        $user = User::factory()->designer()->create();
        $profile = \App\Models\DesignerProfile::factory()->create(['user_id' => $user->id]);
        $design = Design::factory()
            ->for($profile, 'designer')
            ->published()
            ->create();

        $file = UploadedFile::fake()->create('source.pdf', 100);
        app(UploadMediaAction::class)->execute(
            $file, $design, 'print_file', null,
        );

        $this->assertSame(0, DesignProductMapping::count());
    }
}
