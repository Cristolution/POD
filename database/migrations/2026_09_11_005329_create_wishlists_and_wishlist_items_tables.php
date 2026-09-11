<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wishlists + wishlist items.
 *
 * `wishlists` is one row per user (1:1 with users). `wishlist_items` is
 * the many-to-many junction to designs with an added `sort_order` column
 * so the storefront /account page can show items in the order the user
 * added them.
 *
 * Indexing:
 *  - (user_id, design_id) unique — a design only appears once per wishlist.
 *  - (user_id, sort_order) supports the wishlist-page listing in stable order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')
                ->constrained('wishlists')
                ->cascadeOnDelete();
            $table->foreignUuid('design_id')
                ->constrained('designs')
                ->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['wishlist_id', 'design_id']);
            $table->index(['wishlist_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('wishlists');
    }
};
