<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printer_provider_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Pattern A: PK is UUID (public-facing at /printers/{id})
            $table->uuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->timestamps();

            $table->unique('user_id'); // one printer profile per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_provider_profiles');
    }
};
