<?php

declare(strict_types=1);

return [
    'label' => 'Design ↔ product mapping',
    'plural' => 'Design ↔ product mappings',
    'navigation_group' => 'Catalog',

    'sections' => [
        'pairing' => 'Pairing',
        'pricing' => 'Pricing',
        'identity' => 'Identity',
        'identity_description' => 'Design + product pairing and identifier.',
        'fulfillment' => 'Fulfillment',
        'fulfillment_description' => 'Designer-preferred printer for this pairing.',
        'pricing_section' => 'Pricing',
        'pricing_section_description' => 'Final customer-facing price (designer-set).',
        'usage' => 'Usage',
        'usage_description' => 'Carts and orders currently holding this mapping.',
        'lifecycle' => 'Lifecycle',
        'lifecycle_description' => 'Timestamps for creation, last edit, and soft-deletion.',
    ],

    'fields' => [
        'design' => 'Design',
        'product_template' => 'Product template',
        'preferred_printer' => 'Preferred printer',
        'preferred_printer_helper' => 'Designer-preferred fulfiller; informational only.',
        'preferred_printer_missing' => '— no preference set —',
        'final_price' => 'Final price',
        'base_cost_hint' => 'Template base cost',
        'final_price_placeholder' => '— not priced —',
        'in_cart' => 'In shopping carts',
        'in_cart_format' => ':count items',
        'in_orders' => 'In orders',
        'in_orders_format' => ':count items',
        'id' => 'Mapping ID',
        'orphan' => '— orphan —',
        'template_label' => 'Template',
        'created_at' => 'Created',
        'updated_at' => 'Last updated',
        'deleted_at' => 'Deleted at',
    ],

    'filters' => [
        'include_trashed_designs' => 'Include mappings whose design is deleted',
    ],
];
