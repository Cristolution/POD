<?php

declare(strict_types=1);

return [
    'label' => 'Product variant',
    'plural' => 'Product variants',
    'navigation_group' => 'Catalog',

    'sections' => [
        'template' => 'Template',
        'attributes' => 'Attributes (key-value)',
        'attributes_description' => 'E.g. size=L, color=black. Stored as JSON.',
        'pricing_availability' => 'Pricing & availability',
        'identity' => 'Identity',
        'identity_description' => 'SKU, parent template, and identifier.',
        'attributes_section' => 'Attributes',
        'attributes_section_description' => 'Variant option bag (size, colour, material, …).',
        'pricing_section' => 'Pricing',
        'pricing_section_description' => 'Adjustment over the template base cost.',
        'availability' => 'Availability',
        'availability_description' => 'Whether customers can choose this variant.',
        'usage' => 'Usage',
        'usage_description' => 'Where this variant is currently in use.',
        'lifecycle' => 'Lifecycle',
        'lifecycle_description' => 'Timestamps for creation, last edit, and soft-deletion.',
    ],

    'fields' => [
        'product_template' => 'Product template',
        'sku' => 'SKU',
        'sku_missing' => '— no SKU —',
        'attribute_property' => 'Property',
        'attribute_value' => 'Value',
        'attribute_add' => 'Add attribute',
        'no_attributes' => '— no attributes —',
        'display_label' => 'Display label',
        'empty_display_label' => '— empty —',
        'attribute_line_separator' => ': ',
        'price_delta' => 'Price delta',
        'effective_price' => 'Effective price',
        'effective_price_zero' => '$0.00',
        'is_active' => 'Active for sale',
        'in_cart' => 'In shopping carts',
        'in_cart_format' => ':count items',
        'in_orders' => 'In orders',
        'in_orders_format' => ':count items',
        'id' => 'Variant ID',
        'template_orphan' => '— orphaned —',
        'created_at' => 'Created',
        'updated_at' => 'Last updated',
        'deleted_at' => 'Deleted at',
    ],

    'filters' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'template' => 'Template',
    ],
];
