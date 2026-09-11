<?php

declare(strict_types=1);

return [
    'label' => 'Media',
    'plural' => 'Media',
    'navigation_group' => 'Operations',

    'sections' => [
        'owner' => 'Owner',
        'file' => 'File',
    ],

    'fields' => [
        'owner_type' => 'Owner type',
        'owner_type_helper' => 'Polymorphic owner class.',
        'owner_id' => 'Owner ID',
        'owner_id_helper' => 'Primary key of the owner record.',
        'collection' => 'Collection',
        'collection_mockup' => 'Mockup',
        'collection_print_file' => 'Print file',
        'collection_payment_proof' => 'Payment proof',
        'collection_attachment' => 'Attachment',
        'file_path' => 'File path',
        'path' => 'Path',
        'design_owner' => 'Design',
        'payment_owner' => 'Payment',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
    ],
];
