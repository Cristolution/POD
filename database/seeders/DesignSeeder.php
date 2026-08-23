<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\Media;
use App\Models\ProductTemplate;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class DesignSeeder extends Seeder
{
    /**
     * Real seed PNGs committed alongside the SVG sources (gitignored at runtime).
     * Each design in this seeder picks one of these as its primary mockup,
     * plus 1-2 additional mockups and a print file. File paths are stored as
     * `designs/.png` so they resolve via the public storage symlink
     * (i.e. http://app.test/storage/designs/.png).
     */
    private const SEED_IMAGES = [
        'sunset-mountains.png',
        'minimal-wolf.png',
        'cosmic-cat.png',
        'botanical-line.png',
        'geometric-bear.png',
        'retro-sunset.png',
    ];

    public function run(): void
    {
        $designTitles = [
            'Sunset Mountains', 'Minimal Wolf', 'Cyber Tokyo', 'Botanical Line',
            'Cosmic Cat', 'Vintage Camera', 'Geometric Bear', 'Retro Sunset',
            'Neon Skull', 'Pastel Clouds', 'Abstract Wave', 'Typography Quote',
        ];

        $allTags = Tag::all();
        $subCategories = Category::whereNotNull('parent_id')->get();

        // Cycle through the 6 real images so titles share/cascade files evenly.
        $imageCount = count(self::SEED_IMAGES);

        DesignerProfile::all()->each(function (DesignerProfile $designer) use ($designTitles, $allTags, $subCategories, $imageCount): void {
            foreach (array_slice($designTitles, 0, 4) as $index => $title) {
                // Primary mockup for this design — deterministic per (designer, index).
                $primaryImage = self::SEED_IMAGES[($index + abs((int) crc32($designer->id))) % $imageCount];

                /** @var Design $design */
                $design = Design::factory()
                    ->for($designer, 'designer')
                    ->published()
                    ->create([
                        'title' => $title,
                        'category_id' => $subCategories->random()->id,
                    ]);

                // Attach 2-4 random tags
                $design->tags()->attach(
                    $allTags->random(fake()->numberBetween(2, 4))->pluck('id')->toArray()
                );

                // Media: primary mockup + one alternate mockup + one print file.
                // file_path uses the public disk so it resolves via /storage/{path}.
                Media::factory()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                    'collection_name' => 'mockup',
                    'file_path' => 'designs/'.$primaryImage,
                ]);
                Media::factory()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                    'collection_name' => 'mockup',
                    'file_path' => 'designs/'.self::SEED_IMAGES[($index + 1) % $imageCount],
                ]);
                Media::factory()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                    'collection_name' => 'print_file',
                    'file_path' => 'designs/'.$primaryImage,
                ]);

                // Map this design to 1-2 product templates, preferred_printer = the template's owner
                $templates = ProductTemplate::inRandomOrder()
                    ->take(fake()->numberBetween(1, 2))
                    ->get();

                foreach ($templates as $template) {
                    DesignProductMapping::factory()->create([
                        'design_id' => $design->id,
                        'product_template_id' => $template->id,
                        'preferred_printer_id' => $template->printer_provider_id,
                        'final_price' => fake()->randomFloat(2, 15, 80),
                    ]);
                }
            }
        });
    }
}
