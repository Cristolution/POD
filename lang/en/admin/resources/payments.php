<?php

declare(strict_types=1);

return [
    'label' => 'Payment',
    'plural' => 'Payments',
    'navigation_group' => 'Operations',

    'sections' => [
        'order_method' => 'Order & method',
        'confirmation' => 'Confirmation',
        'confirmation_description' => 'Set by the admin who confirms the payment.',
        'payment' => 'Payment',
    ],

    'fields' => [
        'order' => 'Order',
        'method' => 'Method',
        'method_cash_on_delivery' => 'Cash on delivery',
        'method_bank_transfer' => 'Bank transfer',
        'method_card' => 'Card',
        'status' => 'Status',
        'status_pending' => 'Pending',
        'status_confirmed' => 'Confirmed',
        'status_rejected' => 'Rejected',
        'confirmed_by' => 'Confirmed by',
        'confirmed_by_admin' => 'Confirmed by admin',
        'confirmed_at' => 'Confirmed at',
        'id' => 'Payment ID',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],
];
