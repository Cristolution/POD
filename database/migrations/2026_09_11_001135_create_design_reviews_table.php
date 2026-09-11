<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer reviews of designs.
 *
 * Scoping: a review is per (customer, design) — one customer can leave at
 * most one review per design. The optional `order_id` field lets us verify
 * the reviewer actually purchased the design (a future enhancement).
 *
 * Indexing strategy:
 *  - PK = (design_id, customer_id) so the "one review per customer per design"
 *    rule is enforced at the database level.
 *  - design_id index supports the public "list reviews for design X" query.
 *  - customer_id index supports "list reviews by customer Y".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('design_id')
                ->constrained('designs')
                ->cascadeOnDelete();

            $table->foreignUuid('customer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->unsignedTinyInteger('rating'); // 1–5, enforced at app layer
            $table->string('title')->nullable();   // optional headline
            $table->text('body')->nullable();      // optional comment

            $table->boolean('is_approved')->default(true)->index();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // One review per (customer, design).
            $table->unique(['design_id', 'customer_id']);
            $table->index(['design_id', 'is_approved', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_reviews');
    }
};
