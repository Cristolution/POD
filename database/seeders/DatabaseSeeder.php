<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters — each seeder depends on the previous:
     *   1. categories (no deps)
     *   2. tags (no deps)
     *   3. delivery companies (no deps)
     *   4. settings (no deps)
     *   5. users (with roles + designer/printer profiles)
     *   6. addresses (depends on users)
     *   7. product templates + variants (depends on printer profiles)
     *   8. designs + tags + design_product_mappings + media (depends on designers, categories, templates)
     *   9. cart items (depends on users + mappings)
     *  10. orders + items + shipments + payments (depends on customers + mappings + printers + delivery companies)
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            TagSeeder::class,
            DeliveryCompanySeeder::class,
            SettingSeeder::class,
            UserSeeder::class,
            AddressSeeder::class,
            ProductTemplateSeeder::class,
            DesignSeeder::class,
            CartSeeder::class,
            OrderSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
