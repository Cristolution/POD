# Laravel Naming Conventions

## Controllers
- **Class:** `SingularNounController`
- **Examples:** `UserController`, `PostController`, `OrderController`
- **File:** `UserController.php`, `PostController.php`
- **Route resource name:** `plural_snake_case`
- **Examples:** `users`, `posts`, `orders`

## Models
- **Class:** `SingularStudlyCase`
- **Examples:** `User`, `Post`, `OrderItem`
- **Migration / table:** `snake_case` and plural
- **Examples:** `users`, `posts`, `order_items`

## Seeders
- **Class:** `SingularNounSeeder` or `NounTableSeeder`
- **Examples:** `UserSeeder`, `PostSeeder`, `UsersTableSeeder`
- **File:** `UserSeeder.php`, `PostSeeder.php`
- **Location:** `database/seeders/`

## Tasks / Jobs
- **Class:** `StudlyCase` and verb-focused
- **Examples:** `ProcessPayment`, `SendWelcomeEmail`, `SyncInventory`
- **File:** `ProcessPayment.php`, `SendWelcomeEmail.php`
- **Location:** `app/Jobs/`

## Helpers
- **Helper functions:** `snake_case`
- **Examples:** `format_date()`, `is_active()`, `full_name()`
- **Helper file:** `helpers.php` or `HelperNameHelpers.php`
- **Common location:** `app/Helpers/`
- **Loading:** via `composer.json` autoload files

## Functions and Methods
- **Methods inside controllers / services:** `camelCase`, verb-first
- **Examples:** `index()`, `store()`, `updateUser()`, `sendNotification()`
- **Private methods:** still `camelCase`
- **Examples:** `validateRequest()`, `prepareData()`

## Variables
- **Style:** `camelCase` or `snake_case` depending on your codebase
- **Examples:** `$user`, `$orderItems`, `$isActive`
- **Tip:** avoid abbreviations unless obvious

## Database and Migrations
- **Table names:** plural, `snake_case`
- **Examples:** `users`, `posts`, `order_items`
- **Column names:** `snake_case`
- **Examples:** `first_name`, `last_name`, `created_at`, `is_active`
- **Foreign keys:** singular model + `_id`
- **Examples:** `user_id`, `post_id`
- **Pivot tables:** `table_a_table_b`
- **Examples:** `role_user`, `permission_user`

## Folder Structure
- `app/Http/Controllers/`
- `app/Models/`
- `app/Jobs/`
- `app/Listeners/`
- `app/Helpers/`
- `database/seeders/`
- `app/Support/`

## Custom Modules
- `app/Modules/Billing/Http/Controllers/`
- `app/Modules/Billing/Models/Invoice.php`