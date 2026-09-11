<?php

declare(strict_types=1);

return [
    'label' => 'User',
    'plural' => 'Users',
    'navigation_group' => 'System',

    'sections' => [
        'profile' => 'Profile',
        'profile_description' => 'Identity and contact details for this account.',
        'security' => 'Security',
        'security_description' => 'Set or rotate the account password. Leave blank to keep the current password.',
        'identity' => 'Identity',
        'identity_description' => 'The public-facing profile for this account.',
        'access' => 'Access',
        'access_description' => 'Role determines admin-panel reach; phone is used for fulfilment notifications.',
        'timestamps' => 'Timestamps',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'Email address',
        'phone' => 'Phone',
        'role' => 'Role',
        'role_admin' => 'Admin',
        'role_designer' => 'Designer',
        'role_printer' => 'Printer',
        'role_customer' => 'Customer',
        'role_self_change_blocked' => 'You cannot change your own role.',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'verified_designer' => 'Verified designer',
        'active' => 'Active',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'deleted_at' => 'Deleted at',
    ],

    'actions' => [
        'toggle_verified' => 'Toggle verified',
    ],
];
