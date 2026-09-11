<?php

declare(strict_types=1);

return [
    'label' => 'Shipment',
    'plural' => 'Shipments',
    'navigation_group' => 'Operations',

    'sections' => [
        'routing' => 'Routing',
        'tracking_status' => 'Tracking & status',
        'status' => 'Status',
    ],

    'fields' => [
        'order' => 'Order',
        'printer_provider' => 'Printer provider',
        'delivery_company' => 'Delivery company',
        'tracking_number' => 'Tracking number',
        'status' => 'Status',
        'status_pending' => 'Pending',
        'status_shipped' => 'Shipped',
        'status_delivered' => 'Delivered',
        'status_returned' => 'Returned',
        'shipped_at' => 'Shipped at',
        'delivered_at' => 'Delivered at',
        'printer' => 'Printer',
        'carrier' => 'Carrier',
        'created_at' => 'Created at',
    ],

    'filters' => [
        'carrier' => 'Carrier',
    ],
];
