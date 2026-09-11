<?php

declare(strict_types=1);

return [
    'label' => 'Tag',
    'plural' => 'Tags',
    'navigation_group' => 'Catalog',

    'sections' => [
        'identity' => 'Identity',
        'identity_description' => 'Tag name and identifier.',
        'usage' => 'Usage',
        'usage_description' => 'How many designs in the catalogue use this tag.',
        'lifecycle' => 'Lifecycle',
        'lifecycle_description' => 'When this tag was added and last touched.',
    ],

    'fields' => [
        'name' => 'Name',
        'id' => 'Tag ID',
        'designs_count' => 'Designs',
        'tagged_designs' => 'Tagged designs',
        'tagged_designs_format' => ':count designs',
        'popularity' => 'Popularity',
        'popularity_hot' => '🌶  Hot — top tier',
        'popularity_active' => '·  Active',
        'popularity_niche' => '·  Niche',
        'popularity_unused' => '·  Unused',
        'created_at' => 'Created',
        'updated_at' => 'Last updated',
    ],
];
