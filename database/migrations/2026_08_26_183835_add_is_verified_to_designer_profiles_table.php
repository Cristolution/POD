<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('designer_profiles', function (Blueprint $table) {
            $table->boolean('is_verified')->default(true)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('designer_profiles', function (Blueprint $table) {
            $table->dropIndex(['is_verified']);
            $table->dropColumn('is_verified');
        });
    }
};
