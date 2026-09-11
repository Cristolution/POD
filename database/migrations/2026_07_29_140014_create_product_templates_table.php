<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_templates', function (Blueprint $table) {
            $table->uuid('id')->primary(); // public-facing PK (Pattern A)
            $table->foreignUuid('printer_provider_id')
                ->constrained('printer_provider_profiles')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // free text, printer-managed, not an enum
            $table->decimal('base_cost', 10, 2);
            $table->json('specs')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // explicit index for printer-dashboard queries ("my templates")
            $table->index('printer_provider_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_templates');
    }
};
