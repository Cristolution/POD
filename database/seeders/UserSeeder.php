<?php

namespace Database\Seeders;

use App\Models\DesignerProfile;
use App\Models\Media;
use App\Models\PrinterProviderProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Maps seeded user email → avatar filename in `storage/app/public/avatars/`.
     * Reuses a handful of the user's hand-made profile pics so the platform
     * looks lived-in.
     */
    private const AVATAR_MAP = [
        'admin@podmarketplace.test' => 'crist.png',
        'ops@pod.local' => 'crist.png',
        'lana@designstudio.test' => 'female.png',
        'kenji@inkcraft.test' => 'male.png',
        'maya@artisan.test' => 'female (2).png',
        'owner@printhub.test' => 'male (2).png',
        'press@artisanpress.test' => 'male (3).png',
        'workshop@pixel.test' => 'female (3).png',
        'alice@example.test' => 'female (4).png',
        'bob@example.test' => 'male (4).png',
        'carla@example.test' => 'female (5).png',
        'diego@example.test' => 'male (5).png',
        'emma@example.test' => 'female (6).png',
        'felix@example.test' => 'male (6).png',
        'grace@example.test' => 'female (7).png',
        'hiro@example.test' => 'female (8).png',
        'ines@example.test' => 'female.png',
        'jonas@example.test' => 'male.png',
    ];

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
        // from /storage/avatars/<filename>. Done last so every user above
        // is committed before we look them up by email.
        foreach (self::AVATAR_MAP as $email => $filename) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                continue;
            }

            Media::create([
                'model_type' => User::class,
                'model_id' => $user->id,
                'collection_name' => 'avatar',
                'file_path' => 'avatars/'.$filename,
            ]);
        }
    }
}
