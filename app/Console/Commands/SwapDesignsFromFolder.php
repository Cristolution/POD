<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\Media;
use App\Models\ProductTemplate;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Hard-replace the catalog with PNGs from a local folder.
 *
 * Use case: the platform has shipped with seeded sample artwork
 * ("Sunset Mountains", "Minimal Wolf", etc.) and a real batch of
 * user-supplied artwork needs to take its place. Running this command:
 *
 *   1. Force-deletes every Design row (mapping rows cascade via the
 *      `design_id` FK with `onDelete cascade`).
 *   2. Purges polymorphic Media rows pointing at Design.
 *   3. Deletes the contents of `storage/app/public/designs/`.
 *   4. Reads `*.png` files from the source folder (default
 *      `C:\Users\Crist\Desktop\ADISC\raw for POD`), one per design.
 *   5. Round-robins the new designs across every DesignerProfile.
 *   6. Copies each PNG to `designs/brutalist-NN.png`, publishes it, and
 *      creates one mockup Media + one print_file Media per design.
 *   7. Creates a DesignProductMapping for each available product type
 *      (mug, t-shirt, hoodie, tote bag, phone case, sticker) at a
 *      random final_price of $15–$80. Types with no ProductTemplate
 *      rows (poster, cap) are silently skipped.
 *
 * Order_items and cart_items that previously referenced the old
 * designs are LEFT IN PLACE — they remain valid rows whose
 * `design_product_mapping_id` simply dangles, which the storefront
 * already handles (it ignores missing mappings in the cart/orders
 * fragments and renders a "Design no longer available" placeholder
 * in admin Order views).
 */
#[Signature('designs:swap-from-folder
    {--source= : Directory containing PNG designs to import (defaults to the local raw-for-POD folder)}
    {--title-prefix=Brutalist : Prefix used to build new design titles (e.g. "Brutalist 01")}
    {--dry-run : Report what would change without touching the DB or filesystem}')]
#[Description('Hard-replace the design catalog with PNGs from a local folder (round-robin across designers).')]
class SwapDesignsFromFolder extends Command
{
    /** Print-area fallback — keeps mapped templates usable even when specs JSON is absent. */
    private const PRICE_MIN_CENTS = 1500;

    private const PRICE_MAX_CENTS = 8000;

    public function handle(): int
    {
        $source = $this->option('source')
            ?? 'C:\\Users\\Crist\\Desktop\\ADISC\\raw for POD';

        if (! is_dir($source)) {
            $this->error("Source folder does not exist: {$source}");

            return self::FAILURE;
        }

        $files = collect(glob($source.'/*.png'))
            ->map(fn (string $path): string => str_replace('\\', '/', $path))
            ->unique()
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            $this->error("No PNG files found in: {$source}");

            return self::FAILURE;
        }

        $designers = DesignerProfile::query()->orderBy('id')->get();
        if ($designers->isEmpty()) {
            $this->error('No DesignerProfile rows found — cannot assign new designs.');

            return self::FAILURE;
        }

        // Pick one ProductTemplate per type that has at least one row.
        $templatesByType = ProductTemplate::query()
            ->get()
            ->groupBy('type')
            ->map(fn ($group) => $group->first());

        // Flat list of category ids for random assignment — the designs.category_id
        // column is NOT NULL even though the model's $fillable omits it, so we
        // need a real value to satisfy the schema constraint.
        $categoryIds = Category::query()->pluck('id')->all();
        if ($categoryIds === []) {
            $this->error('No Category rows found — cannot assign category_id to new designs.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            return $this->reportDryRun($source, $files, $designers, $templatesByType);
        }

        $this->info("Importing {$files->count()} designs from: {$source}");
        $this->newLine();

        DB::transaction(function () use ($files, $designers, $templatesByType, $categoryIds) {
            // 1. Snapshot counts so we can report what we wiped.
            $existingDesigns = Design::withTrashed()->count();
            $existingMedia = Media::query()->where('model_type', Design::class)->count();

            // 2. Force-delete designs. The mapping FK uses onDelete cascade, so
            //    design_product_mappings rows go with them. Order_items and
            //    cart_items reference mappings by id and remain in place
            //    (dangling FK — documented in the class docblock).
            Design::withTrashed()->forceDelete();
            Media::query()->where('model_type', Design::class)->delete();

            // 3. Wipe the design storage folder so we don't leak stale files.
            $disk = Storage::disk('public');
            foreach ($disk->files('designs') as $stored) {
                $disk->delete($stored);
            }

            $this->line("  removed: {$existingDesigns} designs, {$existingMedia} media rows, designs/* files");
            $this->newLine();

            $createdDesigns = 0;
            $createdMappings = 0;

            // 4. Import each PNG.
            foreach ($files as $i => $filePath) {
                $designer = $designers[$i % $designers->count()];
                $number = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                $titlePrefix = (string) $this->option('title-prefix');
                $title = "{$titlePrefix} {$number}";
                $storedName = 'brutalist-'.$number.'.png';

                $design = Design::create([
                    'designer_id' => $designer->id,
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                    'title' => $title,
                    'status' => 'published',
                ]);

                // Copy PNG to public storage.
                $disk->put('designs/'.$storedName, file_get_contents($filePath));

                $relativePath = 'designs/'.$storedName;

                // mockup + print_file both point at the same PNG (single artwork).
                Media::create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                    'collection_name' => 'mockup',
                    'file_path' => $relativePath,
                ]);
                Media::create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                    'collection_name' => 'print_file',
                    'file_path' => $relativePath,
                ]);

                // Map to every product type that has at least one template.
                foreach ($templatesByType as $type => $template) {
                    DesignProductMapping::create([
                        'design_id' => $design->id,
                        'product_template_id' => $template->id,
                        'preferred_printer_id' => $template->printer_provider_id,
                        'final_price' => random_int(self::PRICE_MIN_CENTS, self::PRICE_MAX_CENTS) / 100,
                    ]);
                    $createdMappings++;
                }

                $createdDesigns++;
            }

            $this->line("  created: {$createdDesigns} designs, ".($createdDesigns * 2).' media rows, '.$createdMappings.' mappings');
            $this->newLine();

            $this->table(
                ['metric', 'count'],
                [
                    ['designs (published)', Design::where('status', 'published')->count()],
                    ['designers used', $designers->count()],
                    ['media rows (Design)', Media::query()->where('model_type', Design::class)->count()],
                    ['mappings', DesignProductMapping::count()],
                    ['product types mapped', $templatesByType->keys()->implode(', ')],
                ],
            );
        });

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, string>  $files
     * @param  Collection<int, DesignerProfile>  $designers
     * @param  Collection<string, ProductTemplate>  $templatesByType
     */
    private function reportDryRun(
        string $source,
        Collection $files,
        Collection $designers,
        Collection $templatesByType,
    ): int {
        $this->warn('DRY RUN — no changes will be made.');
        $this->newLine();
        $this->line("Source: {$source}");
        $this->line('Found '.count($files).' PNG file(s):');
        foreach ($files as $i => $f) {
            $designer = $designers[$i % $designers->count()];
            $this->line(sprintf(
                '  [%02d] %s → %s (%s)',
                $i + 1,
                basename($f),
                $designer->user?->name ?? '?',
                $designer->id,
            ));
        }
        $this->newLine();
        $this->line('Product types with at least one template: '.($templatesByType->isEmpty() ? 'NONE' : $templatesByType->keys()->implode(', ')));
        $this->line('Estimated new mappings: '.(count($files) * $templatesByType->count()));

        return self::SUCCESS;
    }
}
