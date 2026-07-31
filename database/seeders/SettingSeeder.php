<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'platform.name', 'value' => 'POD Marketplace'],
            ['key' => 'platform.commission_rate', 'value' => '0.05'], // 5%
            ['key' => 'platform.currency', 'value' => 'USD'],
            ['key' => 'platform.support_email', 'value' => 'support@podmarketplace.test'],
            ['key' => 'features.cart_guest', 'value' => 'false'],
            ['key' => 'features.designer_analytics', 'value' => 'true'],
            ['key' => 'features.auto_archive_drafts_days', 'value' => '90'],
            ['key' => 'mail.from_address', 'value' => 'no-reply@podmarketplace.test'],
            ['key' => 'mail.from_name', 'value' => 'POD Marketplace'],
        ];

        foreach ($defaults as $row) {
            Setting::factory()->create($row);
        }
    }
}
