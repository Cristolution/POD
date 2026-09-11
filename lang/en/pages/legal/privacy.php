<?php

declare(strict_types=1);

return [
    'title' => 'Privacy policy',
    'heading' => 'Privacy policy',
    'effective' => 'Effective: 2026-08-23',
    'sections' => [
        'data_heading' => '1. Data we collect',
        'data_account' => 'Account data: name, email, phone (optional), role.',
        'data_order' => 'Order data: shipping + billing address, payment method (provider token only — we do not store card numbers), order history.',
        'data_designs' => 'Designs: any media you upload as a designer.',
        'data_usage' => 'Usage data: requests are logged for 30 days for security and debugging.',
        'use_heading' => '2. How we use it',
        'use_body' => 'To operate the Service: fulfill orders, process payments, send transactional notifications (order placed / shipped / delivered), prevent fraud, and improve the platform.',
        'sharing_heading' => '3. Sharing',
        'sharing_body' => 'We share data only with the parties needed to fulfill your order (the printer assigned to each order item; the delivery company; the payment provider). We do not sell personal data.',
        'cookies_heading' => '4. Cookies and tokens',
        'cookies_body' => 'We use a session cookie for web authentication and an optional Sanctum bearer token (stored in an HttpOnly cookie) for API access. No third-party advertising cookies.',
        'retention_heading' => '5. Retention',
        'retention_body' => 'Account data is retained while the account is active. Soft-deleted records are purged after 90 days. Order records are retained for 7 years for tax compliance.',
        'rights_heading' => '6. Your rights',
        'rights_body' => 'You can export your account data (settings → account export) and request deletion at any time, subject to retention requirements.',
        'contact_heading' => '7. Contact',
        'contact_body' => 'Privacy questions: :email.',
    ],
    'note' => 'NOTE: this is v1.2 platform privacy policy. Production launch requires review by counsel before public exposure.',
];
