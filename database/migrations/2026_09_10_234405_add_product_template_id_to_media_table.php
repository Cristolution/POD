<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `media.product_template_id` so a designer can upload an OPTIONAL
 * per-product mockup (e.g. "this design painted on a t-shirt").
 *
 * A media row with `product_template_id = NULL` is the design's main
 * mockup (the existing behaviour). A row with a non-null value is a
 * product-specific override shown on the storefront design page when
 * the customer is browsing that product type.
 *
 * `collection_name = 'mockup'` for both cases — they're semantically the
 * same thing (customer-facing preview), just for different products.
 * The `print_file` collection is unchanged and always applies to the
 * design as a whole (the printer gets one production-ready file).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->foreignUuid('product_template_id')
                ->nullable()
                ->after('collection_name')
                ->constrained('product_templates')
                ->nullOnDelete();

            $table->index(['model_type', 'model_id', 'collection_name', 'product_template_id'], 'media_owner_mockup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('media_owner_mockup_idx');
            $table->dropConstrainedForeignId('product_template_id');
        });
    }
};
