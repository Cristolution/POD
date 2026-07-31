<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        // Each customer gets 1-2 addresses
        User::where('role', 'customer')->each(function (User $user) {
            Address::factory()->create([
                'user_id' => $user->id,
                'country' => fake()->randomElement(['United States', 'United Kingdom', 'Germany', 'France', 'Canada']),
            ]);

            // Half the customers get a second address
            if (fake()->boolean()) {
                Address::factory()->create([
                    'user_id' => $user->id,
                    'country' => fake()->randomElement(['Japan', 'Australia', 'Brazil', 'Spain']),
                ]);
            }
        });
    }
}
