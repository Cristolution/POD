<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->uuid('design_product_mapping_id')
                ->constrained('design_product_mappings')->cascadeOnDelete();
            $table->uuid('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            // Generated key: NULL product_variant_id coalesces to the zero-UUID
            // so the unique constraint catches duplicates even when no variant is
            // selected. SQLite and MySQL treat NULL as distinct in unique indexes,
            // which would otherwise allow duplicate "(user, mapping, NULL variant)"
            // rows. Application layer should upsert (merge quantities) on conflict.
            $table->uuid('variant_key')
                ->storedAs("COALESCE(product_variant_id, '00000000-0000-0000-0000-000000000000')");

            $table->unique(
                ['user_id', 'design_product_mapping_id', 'variant_key'],
                'cart_items_unique_line'
            );

            // explicit index for "my cart" lookups
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
