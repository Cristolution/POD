<?php

declare(strict_types=1);

return [
    'label' => 'Delivery company',
    'plural' => 'Delivery companies',
    'navigation_group' => 'Operations',

    'fields' => [
        'name' => 'Name',
        'coverage_zones' => 'Coverage zones',
        'coverage_zones_placeholder' => 'Add a zone (e.g. EU, US, CA)',
        'coverage_zones_helper' => 'Regions the delivery company serves. Stored as a JSON array.',
        'tracking_url_pattern' => 'Tracking URL pattern',
        'tracking_url_pattern_placeholder' => 'https://track.example.com/{number}',
        'tracking_url_pattern_helper' => 'Use {number} as the placeholder for the tracking number.',
        'zones' => 'Zones',
        'tracking_url' => 'Tracking URL',
        'shipments_count' => 'Shipments',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],
];
