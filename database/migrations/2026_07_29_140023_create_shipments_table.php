<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id')
                ->constrained('orders')->cascadeOnDelete();
            $table->uuid('printer_provider_id')
                ->constrained('printer_provider_profiles')->restrictOnDelete();
            $table->foreignId('delivery_company_id')
                ->constrained('delivery_companies')->restrictOnDelete();
            $table->string('tracking_number')->nullable();
            $table->enum('status', ['pending', 'shipped', 'delivered', 'returned'])
                ->default('pending');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            // explicit indexes for printer-dashboard and delivery-company reporting
            $table->index('printer_provider_id');
            $table->index('delivery_company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
