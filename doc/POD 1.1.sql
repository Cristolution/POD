CREATE TABLE `user` (
  `id` uuid PRIMARY KEY,
  `email` varchar(255) UNIQUE NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(255),
  `created_at` timestamp
);

CREATE TABLE `role` (
  `id` uuid PRIMARY KEY,
  `name` varchar(255) UNIQUE NOT NULL
);

CREATE TABLE `permission` (
  `id` uuid PRIMARY KEY,
  `name` varchar(255) UNIQUE NOT NULL
);

CREATE TABLE `role_permission` (
  `id` uuid PRIMARY KEY,
  `role_id` uuid NOT NULL,
  `permission_id` uuid NOT NULL
);

CREATE TABLE `user_role` (
  `id` uuid PRIMARY KEY,
  `user_id` uuid NOT NULL,
  `role_id` uuid NOT NULL
);

CREATE TABLE `saved_filter` (
  `id` uuid PRIMARY KEY,
  `user_id` uuid NOT NULL,
  `view_name` varchar(255) NOT NULL,
  `filter_json` text NOT NULL,
  `created_at` timestamp
);

CREATE TABLE `table_column_preference` (
  `id` uuid PRIMARY KEY,
  `user_id` uuid NOT NULL,
  `view_name` varchar(255) NOT NULL,
  `visible_columns` text NOT NULL
);

CREATE TABLE `print_provider_status` (
  `id` uuid PRIMARY KEY,
  `code` varchar(255) UNIQUE NOT NULL,
  `label` varchar(255)
);

CREATE TABLE `agreement_status` (
  `id` uuid PRIMARY KEY,
  `code` varchar(255) UNIQUE NOT NULL,
  `label` varchar(255)
);

CREATE TABLE `sub_order_status` (
  `id` uuid PRIMARY KEY,
  `code` varchar(255) UNIQUE NOT NULL,
  `label` varchar(255)
);

CREATE TABLE `payment_status` (
  `id` uuid PRIMARY KEY,
  `code` varchar(255) UNIQUE NOT NULL,
  `label` varchar(255)
);

CREATE TABLE `custom_request_status` (
  `id` uuid PRIMARY KEY,
  `code` varchar(255) UNIQUE NOT NULL,
  `label` varchar(255)
);

CREATE TABLE `print_method` (
  `id` uuid PRIMARY KEY,
  `code` varchar(255) UNIQUE NOT NULL,
  `label` varchar(255)
);

CREATE TABLE `file_format` (
  `id` uuid PRIMARY KEY,
  `code` varchar(255) UNIQUE NOT NULL,
  `label` varchar(255)
);

CREATE TABLE `customer` (
  `id` uuid PRIMARY KEY,
  `user_id` uuid NOT NULL,
  `display_name` varchar(255),
  `created_at` timestamp
);

CREATE TABLE `designer` (
  `id` uuid PRIMARY KEY,
  `user_id` uuid NOT NULL,
  `display_name` varchar(255),
  `created_at` timestamp
);

CREATE TABLE `print_provider` (
  `id` uuid PRIMARY KEY,
  `user_id` uuid NOT NULL,
  `business_name` varchar(255),
  `status_id` uuid NOT NULL,
  `created_at` timestamp
);

CREATE TABLE `material` (
  `id` uuid PRIMARY KEY,
  `name` varchar(255),
  `print_method_id` uuid NOT NULL
);

CREATE TABLE `design` (
  `id` uuid PRIMARY KEY,
  `designer_id` uuid NOT NULL,
  `title` varchar(255),
  `base_file_url` varchar(255) NOT NULL,
  `file_format_id` uuid NOT NULL,
  `created_at` timestamp
);

CREATE TABLE `design_material` (
  `id` uuid PRIMARY KEY,
  `design_id` uuid NOT NULL,
  `material_id` uuid NOT NULL,
  `assigned_provider_id` uuid,
  `price` decimal NOT NULL,
  `is_active` boolean DEFAULT true
);

CREATE TABLE `printer_capability` (
  `id` uuid PRIMARY KEY,
  `provider_id` uuid NOT NULL,
  `material_id` uuid NOT NULL,
  `is_supported` boolean DEFAULT true
);

CREATE TABLE `designer_provider_agreement` (
  `id` uuid PRIMARY KEY,
  `designer_id` uuid NOT NULL,
  `provider_id` uuid NOT NULL,
  `status_id` uuid NOT NULL,
  `commission_rate` decimal
);

CREATE TABLE `order` (
  `id` uuid PRIMARY KEY,
  `customer_id` uuid NOT NULL,
  `designer_id` uuid NOT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `created_at` timestamp
);

CREATE TABLE `sub_order` (
  `id` uuid PRIMARY KEY,
  `order_id` uuid NOT NULL,
  `provider_id` uuid NOT NULL,
  `status_id` uuid NOT NULL
);

CREATE TABLE `order_item` (
  `id` uuid PRIMARY KEY,
  `sub_order_id` uuid NOT NULL,
  `design_material_id` uuid NOT NULL,
  `quantity` int NOT NULL DEFAULT 1,
  `unit_designer_price` decimal NOT NULL,
  `unit_platform_fee` decimal NOT NULL,
  `unit_customer_price` decimal NOT NULL
);

CREATE TABLE `shipment` (
  `id` uuid PRIMARY KEY,
  `sub_order_id` uuid NOT NULL,
  `carrier` varchar(255),
  `tracking_number` varchar(255),
  `shipped_at` timestamp,
  `delivered_at` timestamp
);

CREATE TABLE `tracking_event` (
  `id` uuid PRIMARY KEY,
  `shipment_id` uuid NOT NULL,
  `status` varchar(255),
  `location` varchar(255),
  `occurred_at` timestamp
);

CREATE TABLE `payment` (
  `id` uuid PRIMARY KEY,
  `order_id` uuid,
  `custom_order_request_id` uuid,
  `amount` decimal NOT NULL,
  `status_id` uuid NOT NULL,
  `paid_at` timestamp
);

CREATE TABLE `custom_order_request` (
  `id` uuid PRIMARY KEY,
  `customer_id` uuid NOT NULL,
  `designer_id` uuid NOT NULL,
  `base_design_id` uuid,
  `material_id` uuid,
  `assigned_provider_id` uuid,
  `status_id` uuid NOT NULL,
  `customization_details` text,
  `agreed_price` decimal,
  `carrier` varchar(255),
  `tracking_number` varchar(255),
  `created_at` timestamp
);

CREATE TABLE `custom_request_message` (
  `id` uuid PRIMARY KEY,
  `custom_order_request_id` uuid NOT NULL,
  `sender_user_id` uuid NOT NULL,
  `body` text NOT NULL,
  `sent_at` timestamp
);

CREATE UNIQUE INDEX `role_permission_index_0` ON `role_permission` (`role_id`, `permission_id`);

CREATE UNIQUE INDEX `user_role_index_1` ON `user_role` (`user_id`, `role_id`);

CREATE UNIQUE INDEX `design_material_index_2` ON `design_material` (`design_id`, `material_id`);

CREATE UNIQUE INDEX `printer_capability_index_3` ON `printer_capability` (`provider_id`, `material_id`);

CREATE UNIQUE INDEX `designer_provider_agreement_index_4` ON `designer_provider_agreement` (`designer_id`, `provider_id`);

ALTER TABLE `order` COMMENT = 'overall status is derived from sub_order statuses at read time, not stored';

ALTER TABLE `sub_order` COMMENT = 'independent lifecycle — one sub_order failing does not affect siblings';

ALTER TABLE `payment` COMMENT = 'app-level constraint: exactly one of order_id / custom_order_request_id must be non-null';

ALTER TABLE `custom_order_request` COMMENT = 'permanent standalone record — never converts into order/order_item';

ALTER TABLE `role_permission` ADD FOREIGN KEY (`role_id`) REFERENCES `role` (`id`);

ALTER TABLE `role_permission` ADD FOREIGN KEY (`permission_id`) REFERENCES `permission` (`id`);

ALTER TABLE `user_role` ADD FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `user_role` ADD FOREIGN KEY (`role_id`) REFERENCES `role` (`id`);

ALTER TABLE `saved_filter` ADD FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `table_column_preference` ADD FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `customer` ADD FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `designer` ADD FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `print_provider` ADD FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `print_provider` ADD FOREIGN KEY (`status_id`) REFERENCES `print_provider_status` (`id`);

ALTER TABLE `material` ADD FOREIGN KEY (`print_method_id`) REFERENCES `print_method` (`id`);

ALTER TABLE `design` ADD FOREIGN KEY (`designer_id`) REFERENCES `designer` (`id`);

ALTER TABLE `design` ADD FOREIGN KEY (`file_format_id`) REFERENCES `file_format` (`id`);

ALTER TABLE `design_material` ADD FOREIGN KEY (`design_id`) REFERENCES `design` (`id`);

ALTER TABLE `design_material` ADD FOREIGN KEY (`material_id`) REFERENCES `material` (`id`);

ALTER TABLE `design_material` ADD FOREIGN KEY (`assigned_provider_id`) REFERENCES `print_provider` (`id`);

ALTER TABLE `printer_capability` ADD FOREIGN KEY (`provider_id`) REFERENCES `print_provider` (`id`);

ALTER TABLE `printer_capability` ADD FOREIGN KEY (`material_id`) REFERENCES `material` (`id`);

ALTER TABLE `designer_provider_agreement` ADD FOREIGN KEY (`designer_id`) REFERENCES `designer` (`id`);

ALTER TABLE `designer_provider_agreement` ADD FOREIGN KEY (`provider_id`) REFERENCES `print_provider` (`id`);

ALTER TABLE `designer_provider_agreement` ADD FOREIGN KEY (`status_id`) REFERENCES `agreement_status` (`id`);

ALTER TABLE `order` ADD FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`);

ALTER TABLE `order` ADD FOREIGN KEY (`designer_id`) REFERENCES `designer` (`id`);

ALTER TABLE `sub_order` ADD FOREIGN KEY (`order_id`) REFERENCES `order` (`id`);

ALTER TABLE `sub_order` ADD FOREIGN KEY (`provider_id`) REFERENCES `print_provider` (`id`);

ALTER TABLE `sub_order` ADD FOREIGN KEY (`status_id`) REFERENCES `sub_order_status` (`id`);

ALTER TABLE `order_item` ADD FOREIGN KEY (`sub_order_id`) REFERENCES `sub_order` (`id`);

ALTER TABLE `order_item` ADD FOREIGN KEY (`design_material_id`) REFERENCES `design_material` (`id`);

ALTER TABLE `shipment` ADD FOREIGN KEY (`sub_order_id`) REFERENCES `sub_order` (`id`);

ALTER TABLE `tracking_event` ADD FOREIGN KEY (`shipment_id`) REFERENCES `shipment` (`id`);

ALTER TABLE `payment` ADD FOREIGN KEY (`order_id`) REFERENCES `order` (`id`);

ALTER TABLE `payment` ADD FOREIGN KEY (`custom_order_request_id`) REFERENCES `custom_order_request` (`id`);

ALTER TABLE `payment` ADD FOREIGN KEY (`status_id`) REFERENCES `payment_status` (`id`);

ALTER TABLE `custom_order_request` ADD FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`);

ALTER TABLE `custom_order_request` ADD FOREIGN KEY (`designer_id`) REFERENCES `designer` (`id`);

ALTER TABLE `custom_order_request` ADD FOREIGN KEY (`base_design_id`) REFERENCES `design` (`id`);

ALTER TABLE `custom_order_request` ADD FOREIGN KEY (`material_id`) REFERENCES `material` (`id`);

ALTER TABLE `custom_order_request` ADD FOREIGN KEY (`assigned_provider_id`) REFERENCES `print_provider` (`id`);

ALTER TABLE `custom_order_request` ADD FOREIGN KEY (`status_id`) REFERENCES `custom_request_status` (`id`);

ALTER TABLE `custom_request_message` ADD FOREIGN KEY (`custom_order_request_id`) REFERENCES `custom_order_request` (`id`);

ALTER TABLE `custom_request_message` ADD FOREIGN KEY (`sender_user_id`) REFERENCES `user` (`id`);
