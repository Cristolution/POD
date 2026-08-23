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
    public function run(): void
    {
        $designTitles = [
            'Sunset Mountains', 'Minimal Wolf', 'Cyber Tokyo', 'Botanical Line',
            'Cosmic Cat', 'Vintage Camera', 'Geometric Bear', 'Retro Sunset',
            'Neon Skull', 'Pastel Clouds', 'Abstract Wave', 'Typography Quote',
        ];

        $allTags = Tag::all();
        $subCategories = Category::whereNotNull('parent_id')->get();

        DesignerProfile::all()->each(function (DesignerProfile $designer) use ($designTitles, $allTags, $subCategories) {
            foreach (array_slice($designTitles, 0, 4) as $title) {
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

                // Attach 1-2 mockups + 1 print file as media
                Media::factory()->mockup()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                ]);
                Media::factory()->mockup()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
                ]);
                Media::factory()->printFile()->create([
                    'model_type' => Design::class,
                    'model_id' => $design->id,
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
