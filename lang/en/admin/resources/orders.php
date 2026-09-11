<?php

declare(strict_types=1);

return [
    'label' => 'Order',
    'plural' => 'Orders',
    'navigation_group' => 'Operations',

    'sections' => [
        'order_summary' => 'Order summary',
        'shipping_snapshot' => 'Shipping snapshot',
        'shipping_snapshot_description' => 'Captured at checkout — preserved even if the address book changes.',
        'timestamps' => 'Timestamps',
    ],

    'fields' => [
        'id' => 'Order ID',
        'customer' => 'Customer',
        'status' => 'Status',
        'status_pending' => 'Pending',
        'status_paid' => 'Paid',
        'status_processing' => 'Processing',
        'status_shipped' => 'Shipped',
        'status_delivered' => 'Delivered',
        'status_cancelled' => 'Cancelled',
        'total_amount' => 'Total amount',
        'items_count' => 'Items',
        'created_at' => 'Placed at',
        'updated_at' => 'Updated at',
        'shipping_line1' => 'Shipping address',
        'shipping_city' => 'Shipping city',
        'shipping_country' => 'Shipping country',
        'shipping_phone' => 'Shipping phone',
        'deleted_at' => 'Deleted at',
    ],
];
