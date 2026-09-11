<?php

declare(strict_types=1);

return [
    'label' => 'Design',
    'plural' => 'Designs',
    'navigation_group' => 'Catalog',

    'sections' => [
        'ownership' => 'Ownership',
        'catalog_data' => 'Catalog data',
        'tags' => 'Tags',
        'tags_helper' => 'Pick or create tags. Pivots via the design_tag table.',
        'identity' => 'Identity',
        'identity_description' => 'Title and current publication state.',
        'attribution' => 'Attribution',
        'attribution_description' => 'Who owns this design and where it lives in the tree.',
        'catalogue' => 'Catalogue',
        'catalogue_description' => 'Tags and product mappings linked to this design.',
        'lifecycle' => 'Lifecycle',
        'lifecycle_description' => 'Timestamps for creation, last edit, and soft-deletion.',
    ],

    'fields' => [
        'designer' => 'Designer',
        'designer_helper' => 'Designer profile whose owner is the user shown.',
        'category' => 'Category',
        'title' => 'Title',
        'status' => 'Status',
        'status_draft' => 'Draft',
        'status_published' => 'Published',
        'status_archived' => 'Archived',
        'tags_label' => 'Tags',
        'id' => 'Design ID',
        'designer_orphan_placeholder' => '— orphan —',
        'category_orphan_placeholder' => '— uncategorised —',
        'tags_no_tags' => '— no tags —',
        'tags_joined' => ' · ',
        'mappings_count' => 'Mappings',
        'mappings_count_total' => 'Total mappings',
        'mappings_count_total_format' => ':count mappings',
        'published_mappings' => 'Published mappings',
        'published_mappings_format' => ':count live',
        'media_count' => 'Media files',
        'media_count_format' => ':count files',
        'created_at' => 'Created',
        'updated_at' => 'Last updated',
        'deleted_at' => 'Archived at',
    ],
];
