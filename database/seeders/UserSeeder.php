<?php

namespace Database\Seeders;

use App\Models\DesignerProfile;
use App\Models\Media;
use App\Models\PrinterProviderProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // -- Admin (development) --
        User::factory()->admin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@podmarketplace.test',
        ]);

        // -- Admin (production-style: ops@pod.local) --
        // Kept alongside the dev admin so existing Phase 1-3 test logins
        // remain valid. The prod-style admin's password is overridable
        // via the SEED_ADMIN_PASSWORD env var in .env.
        User::updateOrCreate(
            ['email' => 'ops@pod.local'],
            [
                'name' => 'Ops Admin',
                'role' => 'admin',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'ChangeMe!InProd2026')),
            ]
        );

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
            ['name' => 'Felix Andersson', 'email' => 'felix@example.test'],
            ['name' => 'Grace Mensah',   'email' => 'grace@example.test'],
            ['name' => 'Hiro Nakamura',  'email' => 'hiro@example.test'],
            ['name' => 'Ines Costa',     'email' => 'ines@example.test'],
            ['name' => 'Jonas Berg',     'email' => 'jonas@example.test'],
        ];

        foreach ($customerData as $c) {
            User::factory()->customer()->create($c);
        }

        // -- Avatars: attach a Media row per user so profile pics render
        // from /storage/avatars/<filename>. Round-robin through every PNG
        // in storage/app/public/avatars/ in the order users were created —
        // no per-role/per-gender matching, just "use what we have".
        $avatars = collect(Storage::disk('public')
            ->files('avatars'))
            ->filter(fn ($path) => str_ends_with(strtolower($path), '.png'))
            ->sort()
            ->values();

        if ($avatars->isNotEmpty()) {
            User::orderBy('id')->get()
                ->each(function ($user, $index) use ($avatars) {
                    $filename = $avatars->get($index % $avatars->count());
                    Media::create([
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'collection_name' => 'avatar',
                        'file_path' => $filename,
                    ]);
                });
        }
    }
}
