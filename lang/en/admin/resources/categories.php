<?php

declare(strict_types=1);

return [
    'label' => 'Category',
    'plural' => 'Categories',
    'navigation_group' => 'Catalog',

    'sections' => [
        'identity' => 'Identity',
        'identity_description' => 'Name and hierarchy position.',
        'catalogue' => 'Catalogue',
        'catalogue_description' => 'How this category is used across the catalogue.',
        'lifecycle' => 'Lifecycle',
        'lifecycle_description' => 'When this category was added and last touched.',
    ],

    'fields' => [
        'name' => 'Name',
        'parent' => 'Parent',
        'parent_label' => 'Parent category',
        'root_placeholder' => 'Root category',
        'root_helper' => 'Leave empty for a root category.',
        'orphan_placeholder' => '— root category —',
        'designs_count' => 'Designs',
        'subcategories_count' => 'Direct sub-categories',
        'subcategories_count_format' => ':count sub-categories',
        'designs_in_category' => 'Designs in this category',
        'designs_in_category_format' => ':count designs',
        'created_at' => 'Created',
        'updated_at' => 'Last updated',
    ],

    'filters' => [
        'has_parent' => 'Has parent',
        'root_only' => 'Root only',
        'has_parent_yes' => 'Has parent',
    ],
];
