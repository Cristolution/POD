<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original `create_media_table` migration declared `model_id` as
     * `unsignedBigInteger`, but every polymorphic owner in this app uses
     * `HasUuids` (Design, Order, Payment, Shipment, etc.), so the real
     * values are 36-char UUID strings — SQLite stored them silently in
     * the integer column; MySQL strictly rejects them with `Data
     * truncated`. Widen the column to VARCHAR(36) so polymorphic FKs can
     * actually hold UUIDs.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('model_id', 36)->change();
        });
    }

    /**
     * Reverse the migration — back to unsignedBigInteger for
     * compatibility with the original 2026_07_29 baseline.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->unsignedBigInteger('model_id')->change();
        });
    }
};
