CREATE TABLE `users` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255),
  `email` varchar(255),
  `password` varchar(255),
  `phone` varchar(255),
  `role` varchar(255) COMMENT 'ENUM: customer, designer, printer_provider, admin — fixed, closed set',
  `created_at` timestamp,
  `updated_at` timestamp,
  `deleted_at` timestamp
);

CREATE TABLE `designer_profiles` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `user_id` bigint COMMENT 'FK -> users.id',
  `bio` text
);

CREATE TABLE `printer_provider_profiles` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `user_id` bigint COMMENT 'FK -> users.id',
  `company_name` varchar(255)
);

CREATE TABLE `addresses` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `user_id` bigint COMMENT 'FK -> users.id',
  `line1` varchar(255),
  `city` varchar(255),
  `country` varchar(255),
  `phone` varchar(255)
);

CREATE TABLE `categories` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255),
  `parent_id` bigint COMMENT 'nullable, self-reference'
);

CREATE TABLE `tags` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255)
);

CREATE TABLE `design_tag` (
  `design_id` bigint COMMENT 'FK -> designs.id',
  `tag_id` bigint COMMENT 'FK -> tags.id'
);

CREATE TABLE `product_templates` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `printer_provider_id` bigint COMMENT 'FK',
  `name` varchar(255),
  `type` varchar(255) COMMENT 'free text / printer-managed, not a fixed enum',
  `base_cost` decimal,
  `specs` json
);

CREATE TABLE `product_variants` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `product_template_id` bigint COMMENT 'FK',
  `attributes` json COMMENT 'e.g. size, color, material',
  `price_delta` decimal,
  `sku` varchar(255) COMMENT 'nullable',
  `is_active` boolean
);

CREATE TABLE `designs` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `designer_id` bigint COMMENT 'FK',
  `category_id` bigint COMMENT 'FK',
  `title` varchar(255),
  `status` varchar(255) COMMENT 'ENUM: draft, published, archived — fixed, closed set',
  `created_at` timestamp,
  `updated_at` timestamp,
  `deleted_at` timestamp
);

CREATE TABLE `design_product_mappings` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `design_id` bigint COMMENT 'FK',
  `product_template_id` bigint COMMENT 'FK',
  `preferred_printer_id` bigint COMMENT 'FK',
  `final_price` decimal,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `cart_items` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `user_id` bigint COMMENT 'FK',
  `design_product_mapping_id` bigint COMMENT 'FK',
  `product_variant_id` bigint COMMENT 'FK — nullable, size/color/material choice',
  `quantity` int,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `orders` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `customer_id` bigint COMMENT 'FK',
  `shipping_address_id` bigint COMMENT 'FK — reference only, not source of truth post-checkout',
  `shipping_line1` varchar(255) COMMENT 'snapshot at checkout time',
  `shipping_city` varchar(255) COMMENT 'snapshot at checkout time',
  `shipping_country` varchar(255) COMMENT 'snapshot at checkout time',
  `shipping_phone` varchar(255) COMMENT 'snapshot at checkout time',
  `status` varchar(255) COMMENT 'ENUM: pending, paid, processing, shipped, delivered, cancelled — fixed, closed set',
  `total_amount` decimal,
  `created_at` timestamp,
  `updated_at` timestamp,
  `deleted_at` timestamp
);

CREATE TABLE `order_items` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `order_id` bigint COMMENT 'FK',
  `design_product_mapping_id` bigint COMMENT 'FK',
  `product_variant_id` bigint COMMENT 'FK — nullable',
  `printer_provider_id` bigint COMMENT 'FK — which printer fulfills THIS item. Replaces sub_orders: no separate batch entity, each item tracked independently',
  `status` varchar(255) COMMENT 'ENUM: pending, received, printing, printed, handed_off, cancelled — fixed, closed set. Per-item fulfillment status, replaces sub_orders.status',
  `quantity` int,
  `unit_price` decimal,
  `created_at` timestamp,
  `updated_at` timestamp
);

CREATE TABLE `delivery_companies` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255),
  `coverage_zones` json,
  `tracking_url_pattern` varchar(255)
);

CREATE TABLE `shipments` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `order_id` bigint COMMENT 'FK — one order can have multiple shipments if items came from different printers',
  `printer_provider_id` bigint COMMENT 'FK — identifies which printer''s items this shipment covers (logical grouping, not an enforced junction to order_items)',
  `delivery_company_id` bigint COMMENT 'FK',
  `tracking_number` varchar(255),
  `status` varchar(255) COMMENT 'ENUM: pending, shipped, delivered, returned — fixed, closed set',
  `shipped_at` timestamp,
  `delivered_at` timestamp
);

CREATE TABLE `payments` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `order_id` bigint COMMENT 'FK',
  `method` varchar(255) COMMENT 'ENUM: cash_on_delivery, bank_transfer, card — fixed for now',
  `status` varchar(255) COMMENT 'ENUM: pending, confirmed, rejected — fixed, closed set',
  `confirmed_by_admin_id` bigint COMMENT 'FK — nullable',
  `confirmed_at` timestamp,
  `created_at` timestamp,
  `deleted_at` timestamp
);

CREATE TABLE `media` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `model_type` varchar(255) COMMENT 'polymorphic owner',
  `model_id` bigint,
  `collection_name` varchar(255) COMMENT 'ENUM: mockup, print_file, payment_proof, attachment — fixed, closed set',
  `file_path` varchar(255)
);

CREATE TABLE `notifications` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `notifiable_type` varchar(255),
  `notifiable_id` bigint,
  `type` varchar(255) COMMENT 'NOT an enum — new notification types get added as features ship',
  `data` json,
  `read_at` timestamp
);

CREATE TABLE `settings` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `key` varchar(255),
  `value` text
);

CREATE INDEX `idx_categories_parent` ON `categories` (`parent_id`);

CREATE UNIQUE INDEX `design_tag_pk` ON `design_tag` (`design_id`, `tag_id`);

CREATE UNIQUE INDEX `dpm_design_template_unique` ON `design_product_mappings` (`design_id`, `product_template_id`);

ALTER TABLE `payments` COMMENT = 'proof-of-payment image lives in MEDIA (model_type=Payment, collection_name=payment_proof)';

ALTER TABLE `designer_profiles` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `printer_provider_profiles` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `addresses` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `categories` ADD FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`);

ALTER TABLE `design_tag` ADD FOREIGN KEY (`design_id`) REFERENCES `designs` (`id`);

ALTER TABLE `design_tag` ADD FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`);

ALTER TABLE `product_templates` ADD FOREIGN KEY (`printer_provider_id`) REFERENCES `printer_provider_profiles` (`id`);

ALTER TABLE `product_variants` ADD FOREIGN KEY (`product_template_id`) REFERENCES `product_templates` (`id`);

ALTER TABLE `designs` ADD FOREIGN KEY (`designer_id`) REFERENCES `designer_profiles` (`id`);

ALTER TABLE `designs` ADD FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

ALTER TABLE `design_product_mappings` ADD FOREIGN KEY (`design_id`) REFERENCES `designs` (`id`);

ALTER TABLE `design_product_mappings` ADD FOREIGN KEY (`product_template_id`) REFERENCES `product_templates` (`id`);

ALTER TABLE `design_product_mappings` ADD FOREIGN KEY (`preferred_printer_id`) REFERENCES `printer_provider_profiles` (`id`);

ALTER TABLE `cart_items` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `cart_items` ADD FOREIGN KEY (`design_product_mapping_id`) REFERENCES `design_product_mappings` (`id`);

ALTER TABLE `cart_items` ADD FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`);

ALTER TABLE `orders` ADD FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

ALTER TABLE `orders` ADD FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses` (`id`);

ALTER TABLE `order_items` ADD FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

ALTER TABLE `order_items` ADD FOREIGN KEY (`design_product_mapping_id`) REFERENCES `design_product_mappings` (`id`);

ALTER TABLE `order_items` ADD FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`);

ALTER TABLE `order_items` ADD FOREIGN KEY (`printer_provider_id`) REFERENCES `printer_provider_profiles` (`id`);

ALTER TABLE `shipments` ADD FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

ALTER TABLE `shipments` ADD FOREIGN KEY (`printer_provider_id`) REFERENCES `printer_provider_profiles` (`id`);

ALTER TABLE `shipments` ADD FOREIGN KEY (`delivery_company_id`) REFERENCES `delivery_companies` (`id`);

ALTER TABLE `payments` ADD FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

ALTER TABLE `payments` ADD FOREIGN KEY (`confirmed_by_admin_id`) REFERENCES `users` (`id`);
