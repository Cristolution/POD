<?php

namespace Database\Seeders;

use App\Models\DesignerProfile;
use App\Models\PrinterProviderProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // -- Admin --
        User::factory()->admin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@podmarketplace.test',
        ]);

        // -- Designers (3) --
        $designerData = [
            ['name' => 'Lana Rivera',  'email' => 'lana@designstudio.test'],
            ['name' => 'Kenji Tanaka', 'email' => 'kenji@inkcraft.test'],
            ['name' => 'Maya Hassan',   'email' => 'maya@artisan.test'],
        ];

        foreach ($designerData as $d) {
            $user = User::factory()->designer()->create($d);
            DesignerProfile::factory()->create([
                'user_id' => $user->id,
                'bio' => fake()->paragraph(),
            ]);
        }

        // -- Printer Providers (3) --
        $printerData = [
            ['name' => 'PrintHub Owner',  'email' => 'owner@printhub.test',     'company_name' => 'PrintHub Co.'],
            ['name' => 'Artisan Press',   'email' => 'press@artisanpress.test', 'company_name' => 'Artisan Press Ltd.'],
            ['name' => 'Pixel Workshop',  'email' => 'workshop@pixel.test',     'company_name' => 'Pixel Workshop Inc.'],
        ];

        foreach ($printerData as $p) {
            $user = User::factory()->printerProvider()->create([
                'name' => $p['name'],
                'email' => $p['email'],
            ]);
            PrinterProviderProfile::factory()->create([
                'user_id' => $user->id,
                'company_name' => $p['company_name'],
            ]);
        }

        // -- Customers (10) --
        $customerData = [
            ['name' => 'Alice Chen',     'email' => 'alice@example.test'],
            ['name' => 'Bob Williams',   'email' => 'bob@example.test'],
            ['name' => 'Carla Rossi',    'email' => 'carla@example.test'],
            ['name' => 'Diego Lopez',    'email' => 'diego@example.test'],
            ['name' => 'Emma Patel',     'email' => 'emma@example.test'],
            ['name' => 'Felix Andersson','email' => 'felix@example.test'],
            ['name' => 'Grace Mensah',   'email' => 'grace@example.test'],
            ['name' => 'Hiro Nakamura',  'email' => 'hiro@example.test'],
            ['name' => 'Ines Costa',     'email' => 'ines@example.test'],
            ['name' => 'Jonas Berg',     'email' => 'jonas@example.test'],
        ];

        foreach ($customerData as $c) {
            User::factory()->customer()->create($c);
        }
    }
}
