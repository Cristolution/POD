<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary(); // public-facing PK (Pattern A)
            $table->foreignUuid('order_id')
                ->constrained('orders')->cascadeOnDelete();
            $table->enum('method', ['cash_on_delivery', 'bank_transfer', 'card'])
                ->index();
            $table->enum('status', ['pending', 'confirmed', 'rejected'])
                ->default('pending')
                ->index();
            $table->foreignUuid('confirmed_by_admin_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
