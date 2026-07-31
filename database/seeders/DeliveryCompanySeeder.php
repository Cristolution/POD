<?php

namespace Database\Seeders;

use App\Models\DeliveryCompany;
use Illuminate\Database\Seeder;

class DeliveryCompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['name' => 'DHL Express', 'tracking_url_pattern' => 'https://www.dhl.com/track?id={number}'],
            ['name' => 'FedEx', 'tracking_url_pattern' => 'https://www.fedex.com/fedextrack/?trknbr={number}'],
            ['name' => 'UPS', 'tracking_url_pattern' => 'https://www.ups.com/track?tracknum={number}'],
            ['name' => 'USPS', 'tracking_url_pattern' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={number}'],
            ['name' => 'Aramex', 'tracking_url_pattern' => 'https://www.aramex.com/track?shipment={number}'],
        ];

        foreach ($companies as $data) {
            DeliveryCompany::factory()->create($data);
        }
    }
}
