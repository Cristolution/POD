<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_tag', function (Blueprint $table) {
            $table->uuid('design_id')
                ->constrained('designs')->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->constrained('tags')->cascadeOnDelete();

            $table->primary(['design_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_tag');
    }
};