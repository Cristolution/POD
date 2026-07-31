<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('design_id')
                ->constrained('designs')->cascadeOnDelete();
            $table->foreignId('product_template_id')
                ->constrained('product_templates')->cascadeOnDelete();
            $table->foreignId('preferred_printer_id')
                ->constrained('printer_provider_profiles')->cascadeOnDelete();
            $table->decimal('final_price', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['design_id', 'product_template_id'], 'dpm_design_template_unique');

            // explicit indexes on FKs for catalog browsing and printer routing
            $table->index('design_id');
            $table->index('product_template_id');
            $table->index('preferred_printer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_product_mappings');
    }
};