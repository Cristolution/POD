<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->timestamps();

            $table->unique('user_id'); // one designer profile per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designer_profiles');
    }
};