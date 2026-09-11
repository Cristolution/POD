<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the unused `name` column from `product_templates`.
 *
 * The column was created by {@see 2026_07_29_140014_create_product_templates_table}
 * but never read anywhere in `app/` or `resources/` — the meaningful
 * identifier for a template is `type` (mug, t-shirt, hoodie, …). The
 * factory populated it with Faker-generated Latin words ("eligendi
 * repellendus", "ullam quisquam", …) which surfaced confusingly on the
 * storefront heading before we fixed that to use `type`.
 *
 * Removing it eliminates the dead-data confusion at the source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_templates', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('product_templates', function (Blueprint $table) {
            $table->string('name');
        });
    }
};
