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
    /** Pod numbers correspond to user-made design mockup folders. */
    private const POD_NUMBERS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];

    /**
     * Maps product type → list of filename suffixes (in pod{N}/ directory).
     * First match wins. Each design gets ONE mockup per type it supports.
     *
     * Suffixes derived from the user-made mockup assets at
     * C:/Users/Crist/Desktop/enhanced mockup data assets/pod{N}/.
     */
    private const PRODUCT_MOCKUPS = [
        'cap' => ['black_cap'],
        'hoodie' => ['black_hoodie', 'grey_hoodie', 'white_hoodie'],
        'mug' => ['black_mug', 'white_mug'],
        'tote bag' => ['black_tote_bag'],
        't-shirt' => ['black_tshirt', 'white_tshirt'],
        'canvas' => ['canvas'],
        'sticker' => ['circle_white_sticker'],
        'poster' => ['framed_white_poster'],
        'long sleeve' => ['grey_long_sleeve'],
        'bottle' => ['white_bottle'],
        'phone case' => ['white_iphone_phone_case'],
        'notebook' => ['white_notebook'],
        'thermos' => ['white_thermos'],
    ];

    public function run(): void
    {
        $designTitles = [
            'Sunset Mountains', 'Minimal Wolf', 'Cyber Tokyo', 'Botanical Line',
            'Cosmic Cat', 'Vintage Camera', 'Geometric Bear', 'Retro Sunset',
            'Neon Skull', 'Pastel Clouds', 'Abstract Wave', 'Typography Quote',
            'Mystic Forest', 'Pixel City', 'Ocean Wave', 'Desert Bloom',
            'Neon Cityscape', 'Vintage Vinyl',
        ];

        $allTags = Tag::all();
        $subCategories = Category::whereNotNull('parent_id')->get();
        $podCount = count(self::POD_NUMBERS);

        // Pre-resolve every product template we want to attach. We use the
        // existing seed data: pick all templates and group them by type so
        // each design can be mapped to one template per type it supports.
        $templatesByType = ProductTemplate::all()
            ->groupBy('type')
            ->map(fn ($group) => $group->first()); // one template per type

        DesignerProfile::all()->each(function (DesignerProfile $designer) use ($designTitles, $allTags, $subCategories, $podCount, $templatesByType): void {
            // 6 designs per designer × 3 designers = 18 designs total.
            foreach (array_slice($designTitles, 0, 6) as $index => $title) {
                $podNumber = self::POD_NUMBERS[$index % $podCount];

                /** @var Design $design */
                $design = Design::factory()
                    ->for($designer, 'designer')
                    ->published()
                    ->create([
                        'title' => $title,
                        'category_id' => $subCategories->random()->id,
                    ]);

                // 2-4 random tags
                $design->tags()->attach(
                    $allTags->random(fake()->numberBetween(2, 4))->pluck('id')->toArray()
                );

                // Primary mockup = base artwork (used by browse card + detail hero).
                Media::factory()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                    'collection_name' => 'mockup',
                    'file_path' => "designs/pod{$podNumber}/base.jpg",
                ]);

                // Print file (high-res source). Reuse base shot.
                Media::factory()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                    'collection_name' => 'print_file',
                    'file_path' => "designs/pod{$podNumber}/base.jpg",
                ]);

                // Map this design to one template PER product type it supports.
                // Each mapping gets a per-product mockup keyed by product_template_id
                // so the detail page shows the correct mockup per material.
                foreach (self::PRODUCT_MOCKUPS as $type => $suffixes) {
                    $template = $templatesByType[$type] ?? null;
                    if ($template === null) {
                        continue; // type not seeded
                    }

                    // Deterministic but varied suffix per design (round-robin).
                    $suffix = $suffixes[$index % count($suffixes)];

                    DesignProductMapping::factory()->create([
                        'design_id' => $design->id,
                        'product_template_id' => $template->id,
                        'preferred_printer_id' => $template->printer_provider_id,
                        'final_price' => fake()->randomFloat(2, 15, 80),
                    ]);

                    Media::factory()->create([
                        'model_type' => Design::class,
                        'model_id' => $design->id,
                        'collection_name' => 'mockup',
                        'product_template_id' => $template->id,
                        'file_path' => "designs/pod{$podNumber}/{$suffix}.jpg",
                    ]);
                }
            }
        });
    }
}
