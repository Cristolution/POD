<?php

namespace Database\Seeders;

use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // The product types we want to guarantee exist in the DB so every
        // facet option has at least one design to surface. These types come
        // straight from the user-made mockup assets (see
        // ProductTemplateFactory::$types) — no mouse pad / laptop sleeves /
        // greeting cards because those assets don't exist.
        $guaranteedTypes = [
            'cap', 'hoodie', 'mug', 'tote bag', 't-shirt', 'canvas',
            'sticker', 'poster', 'long sleeve', 'bottle',
            'phone case', 'notebook', 'thermos',
        ];

        // Seed guaranteed types — one template per type, round-robin assigned
        // to the printers so no printer owns all of them.
        $printers = PrinterProviderProfile::all()->values();
        foreach ($guaranteedTypes as $i => $type) {
            $printer = $printers[$i % max(1, $printers->count())];
            $template = ProductTemplate::factory()
                ->for($printer, 'printerProvider')
                ->create([
                    'type' => $type,
                ]);

            ProductVariant::factory()
                ->count(fake()->numberBetween(2, 4))
                ->for($template, 'productTemplate')
                ->create();
        }

        // Add 1-2 more random templates per printer for variety.
        PrinterProviderProfile::all()->each(function (PrinterProviderProfile $printer) {
            $templates = ProductTemplate::factory()
                ->count(fake()->numberBetween(1, 2))
                ->for($printer, 'printerProvider')
                ->create();

            $templates->each(function (ProductTemplate $template) {
                ProductVariant::factory()
                    ->count(fake()->numberBetween(2, 4))
                    ->for($template, 'productTemplate')
                    ->create();
            });
        });
    }
}
