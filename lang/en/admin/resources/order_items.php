<?php

declare(strict_types=1);

return [
    'label' => 'Order item',
    'plural' => 'Order items',
    'navigation_group' => 'Operations',

    'sections' => [
        'line_item' => 'Line item',
        'references' => 'References',
        'timestamps' => 'Timestamps',
    ],

    'fields' => [
        'id' => 'Order item ID',
        'order' => 'Order',
        'design' => 'Design',
        'variant_sku' => 'Variant SKU',
        'printer' => 'Printer',
        'status' => 'Status',
        'status_pending' => 'Pending',
        'status_received' => 'Received',
        'status_printing' => 'Printing',
        'status_printed' => 'Printed',
        'status_handed_off' => 'Handed off',
        'status_cancelled' => 'Cancelled',
        'quantity' => 'Quantity',
        'unit_price' => 'Unit price',
        'line_total' => 'Line total',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'deleted_at' => 'Deleted at',
    ],
];
