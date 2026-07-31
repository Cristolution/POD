<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary(); // public-facing PK (Pattern A): order-tracking URLs use the UUID
            $table->uuid('customer_id')
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
            ])->default('pending')->index();
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            // explicit indexes for "my orders", admin filtering, and date-range queries
            $table->index('customer_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};