<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->uuid('id')->primary(); // public-facing PK (Pattern A)
            $table->uuid('designer_id')
                ->constrained('designer_profiles')->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained('categories')->restrictOnDelete();
            $table->string('title');
            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft')
                ->index();
            $table->timestamps();
            $table->softDeletes();

            // explicit indexes for catalog browsing and soft-delete perf
            $table->index('designer_id');
            $table->index('category_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};