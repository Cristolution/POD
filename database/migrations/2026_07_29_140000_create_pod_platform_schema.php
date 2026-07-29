<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * POD Platform — full schema, ERD v1.3
 *
 * Single migration file for review. Split into one-migration-per-table
 * later, respecting this same creation order (it already resolves FK
 * dependencies correctly — don't reorder without checking references).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===================== AUTH =====================
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->enum('role', ['customer', 'designer', 'printer_provider', 'admin'])
                ->default('customer');
            $table->timestamps();
            $table->softDeletes();
        });

        // ===================== PROFILES =====================
        Schema::create('designer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->timestamps();

            $table->unique('user_id'); // one designer profile per user
        });

        Schema::create('printer_provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->timestamps();

            $table->unique('user_id'); // one printer profile per user
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('line1');
            $table->string('city');
            $table->string('country');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // ===================== CATALOG / DESIGN =====================
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()
                ->constrained('categories')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('product_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('printer_provider_id')
                ->constrained('printer_provider_profiles')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // free text, printer-managed, not an enum
            $table->decimal('base_cost', 10, 2);
            $table->json('specs')->nullable();
            $table->timestamps();
        });

        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designer_id')
                ->constrained('designer_profiles')->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained('categories')->restrictOnDelete();
            $table->string('title');
            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('design_tag', function (Blueprint $table) {
            $table->foreignId('design_id')
                ->constrained('designs')->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->constrained('tags')->cascadeOnDelete();

            $table->primary(['design_id', 'tag_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_template_id')
                ->constrained('product_templates')->cascadeOnDelete();
            $table->json('attributes'); // e.g. { "size": "L", "color": "black" }
            $table->decimal('price_delta', 10, 2)->default(0);
            $table->string('sku')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('design_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')
                ->constrained('designs')->cascadeOnDelete();
            $table->foreignId('product_template_id')
                ->constrained('product_templates')->cascadeOnDelete();
            $table->foreignId('preferred_printer_id')
                ->constrained('printer_provider_profiles')->cascadeOnDelete();
            $table->decimal('final_price', 10, 2);
            $table->timestamps();

            $table->unique(['design_id', 'product_template_id'], 'dpm_design_template_unique');
        });

        // ===================== CART =====================
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->foreignId('design_product_mapping_id')
                ->constrained('design_product_mappings')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            // a user shouldn't have the exact same mapping+variant twice as separate rows
            $table->unique(['user_id', 'design_product_mapping_id', 'product_variant_id'], 'cart_items_unique_line');
        });

        // ===================== ORDERS / FULFILLMENT =====================
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('shipping_address_id')->nullable()
                ->constrained('addresses')->nullOnDelete();

            // snapshot at checkout time — source of truth for this order regardless
            // of later edits/deletes to the addresses table
            $table->string('shipping_line1');
            $table->string('shipping_city');
            $table->string('shipping_country');
            $table->string('shipping_phone')->nullable();

            $table->enum('status', [
                'pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled',
            ])->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')->cascadeOnDelete();
            $table->foreignId('design_product_mapping_id')
                ->constrained('design_product_mappings')->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->foreignId('printer_provider_id')
                ->constrained('printer_provider_profiles')->restrictOnDelete();

            $table->enum('status', [
                'pending', 'received', 'printing', 'printed', 'handed_off', 'cancelled',
            ])->default('pending');

            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });

        Schema::create('delivery_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('coverage_zones')->nullable();
            $table->string('tracking_url_pattern')->nullable();
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')->cascadeOnDelete();
            $table->foreignId('printer_provider_id')
                ->constrained('printer_provider_profiles')->restrictOnDelete();
            $table->foreignId('delivery_company_id')
                ->constrained('delivery_companies')->restrictOnDelete();
            $table->string('tracking_number')->nullable();
            $table->enum('status', ['pending', 'shipped', 'delivered', 'returned'])
                ->default('pending');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')->cascadeOnDelete();
            $table->enum('method', ['cash_on_delivery', 'bank_transfer', 'card']);
            $table->enum('status', ['pending', 'confirmed', 'rejected'])
                ->default('pending');
            $table->foreignId('confirmed_by_admin_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ===================== SHARED INFRASTRUCTURE =====================
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // polymorphic owner class
            $table->unsignedBigInteger('model_id');
            $table->enum('collection_name', [
                'mockup', 'print_file', 'payment_proof', 'attachment',
            ]);
            $table->string('file_path');
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Laravel's built-in notifications table uses uuid
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('type'); // notification class name — not an enum, grows over time
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // reverse creation order so FKs drop cleanly
        Schema::dropIfExists('settings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('media');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('delivery_companies');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('design_product_mappings');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('design_tag');
        Schema::dropIfExists('designs');
        Schema::dropIfExists('product_templates');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('printer_provider_profiles');
        Schema::dropIfExists('designer_profiles');
        Schema::dropIfExists('users');
    }
};