<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_template_id')
                ->constrained('product_templates')->cascadeOnDelete();
            $table->json('attributes'); // e.g. { "size": "L", "color": "black" }
            $table->decimal('price_delta', 10, 2)->default(0);
            $table->string('sku')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // explicit index for "variants by template" lookups
            $table->index('product_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};