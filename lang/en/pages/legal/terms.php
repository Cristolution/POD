<?php

declare(strict_types=1);

return [
    'title' => 'Terms of service',
    'heading' => 'Terms of service',
    'effective' => 'Effective: 2026-08-23',
    'sections' => [
        'acceptance_heading' => '1. Acceptance',
        'acceptance_body' => 'By creating an account on POD Platform ("the Service") you agree to these terms. If you do not agree, do not use the Service.',
        'accounts_heading' => '2. Accounts and roles',
        'accounts_body' => 'The Service supports four roles: :customer (browses and orders), :designer (publishes designs), :printer_provider (fulfills orders), and :admin (operates the platform). Each role has specific permissions defined in :matrix.',
        'orders_heading' => '3. Orders and payments',
        'orders_body' => 'Orders are placed via the checkout flow at :checkout_path. Prices are snapshotted on :snapshot at placement time. The platform does not charge tax in v1.2.',
        'ip_heading' => '4. Designs and intellectual property',
        'ip_body' => 'Designers retain copyright on designs they upload. By publishing a design you grant the platform a non-exclusive license to reproduce the design on the chosen product templates for the purpose of fulfilling orders.',
        'fulfillment_heading' => '5. Fulfillment and shipping',
        'fulfillment_body' => 'Printers are independent contractors. Delivery times are estimates, not guarantees. Risk of loss passes to the customer upon carrier acceptance.',
        'cancellations_heading' => '6. Cancellations and refunds',
        'cancellations_body' => 'Orders may be cancelled before the status transitions to :shipped. After shipment, refunds are handled case-by-case.',
        'termination_heading' => '7. Account termination',
        'termination_body' => 'We may suspend or terminate accounts that violate these terms or the permissions matrix.',
        'contact_heading' => '8. Contact',
        'contact_body' => 'Questions: :email.',
    ],
    'note' => 'NOTE: this is v1.2 platform terms. Production launch requires review by counsel before public exposure.',
];
