<?php

declare(strict_types=1);

return [
    'label' => 'Address',
    'plural' => 'Addresses',
    'navigation_group' => 'Operations',

    'sections' => [
        'owner' => 'Owner',
        'owner_description' => 'The customer account this address book entry belongs to.',
        'address' => 'Address',
        'timestamps' => 'Timestamps',
    ],

    'fields' => [
        'user' => 'User',
        'user_helper' => 'Owner of the address book entry.',
        'line1' => 'Street address',
        'city' => 'City',
        'country' => 'Country',
        'phone' => 'Phone',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],
];
