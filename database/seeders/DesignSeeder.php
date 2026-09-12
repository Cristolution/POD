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
     * Pod numbers correspond to user-made design mockup folders under
     * `storage/app/public/designs/pod{N}/`. Each design picks its primary
     * mockup from its pod's directory so every design gets a unique image.
     */
    private const POD_NUMBERS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18];

    /**
     * Maps product type → filename suffix used in each pod{N}/ directory.
     * Lets the seeder pick a realistic product-specific mockup when one is
     * available (e.g. mug design → pod1/black_mug.jpg).
     */
    private const PRODUCT_MOCKUPS = [
        'mug' => 'black_mug',
        'hoodie' => 'black_hoodie',
        'tote bag' => 'black_tote_bag',
        'cap' => 'black_cap',
        'phone case' => 'white_iphone_phone_case',
        'sticker' => 'circle_white_sticker',
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

        DesignerProfile::all()->each(function (DesignerProfile $designer) use ($designTitles, $allTags, $subCategories, $podCount): void {
            // 6 designs per designer × 3 designers = 18 designs total
            foreach (array_slice($designTitles, 0, 6) as $index => $title) {
                // Deterministic pod assignment per (designer, index) so we
                // cycle through 6 of the 18 pods across the designers.
                $podNumber = self::POD_NUMBERS[$index % $podCount];

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

                // Default mockup — black mug from this pod's directory.
                Media::factory()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                    'collection_name' => 'mockup',
                    'file_path' => "designs/pod{$podNumber}/black_mug.jpg",
                ]);

                // Alternate mockup — grey hoodie variant from the same pod.
                Media::factory()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                    'collection_name' => 'mockup',
                    'file_path' => "designs/pod{$podNumber}/grey_hoodie.jpg",
                ]);

                // Print file — high-res print source (reuse mug shot).
                Media::factory()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                    'collection_name' => 'print_file',
                    'file_path' => "designs/pod{$podNumber}/black_mug.jpg",
                ]);

                // Map this design to 1-2 product templates, preferred_printer = the template's owner
                $templates = ProductTemplate::inRandomOrder()
                    ->take(fake()->numberBetween(1, 2))
                    ->get();

                foreach ($templates as $template) {
                    $mockupSuffix = self::PRODUCT_MOCKUPS[$template->type] ?? 'black_mug';

                    DesignProductMapping::factory()->create([
                        'design_id' => $design->id,
                        'product_template_id' => $template->id,
                        'preferred_printer_id' => $template->printer_provider_id,
                        'final_price' => fake()->randomFloat(2, 15, 80),
                    ]);

                    // Per-product mockup override using the matching product type.
                    Media::factory()->create([
                        'model_type' => Design::class,
                        'model_id' => $design->id,
                        'collection_name' => 'mockup',
                        'product_template_id' => $template->id,
                        'file_path' => "designs/pod{$podNumber}/{$mockupSuffix}.jpg",
                    ]);
                }
            }
        });
    }
}
