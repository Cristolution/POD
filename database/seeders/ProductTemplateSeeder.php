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
        // Each printer provider owns 3-4 templates, each with 2-4 variants
        PrinterProviderProfile::all()->each(function (PrinterProviderProfile $printer) {
            $templates = ProductTemplate::factory()
                ->count(fake()->numberBetween(3, 4))
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
