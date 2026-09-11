<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id')
                ->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('design_product_mapping_id')
                ->constrained('design_product_mappings')->restrictOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->foreignUuid('printer_provider_id')
                ->constrained('printer_provider_profiles')->restrictOnDelete();

            $table->enum('status', [
                'pending', 'received', 'printing', 'printed', 'handed_off', 'cancelled',
            ])->default('pending')->index();

            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            // explicit index for printer-queue and fulfillment dashboards
            $table->index('printer_provider_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
