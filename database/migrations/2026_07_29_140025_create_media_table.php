<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // polymorphic owner class
            $table->unsignedBigInteger('model_id');
            $table->enum('collection_name', [
                'mockup', 'print_file', 'payment_proof', 'attachment',
            ]);
            $table->string('file_path');
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
