<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $rootCategories = [
            'Apparel' => ['T-Shirts', 'Hoodies', 'Caps', 'Tote Bags'],
            'Drinkware' => ['Mugs', 'Water Bottles', 'Tumblers'],
            'Wall Art' => ['Posters', 'Canvas', 'Framed Prints'],
            'Stationery' => ['Notebooks', 'Stickers', 'Greeting Cards'],
            'Tech' => ['Phone Cases', 'Mousepads', 'Laptop Sleeves'],
        ];

        foreach ($rootCategories as $rootName => $subNames) {
            $root = Category::factory()->create(['name' => $rootName, 'parent_id' => null]);

            foreach ($subNames as $subName) {
                Category::factory()->create([
                    'name' => $subName,
                    'parent_id' => $root->id,
                ]);
            }
        }
    }
}
