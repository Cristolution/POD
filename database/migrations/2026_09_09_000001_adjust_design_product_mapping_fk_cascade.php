<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_product_mappings', function (Blueprint $table) {
            // The original migration declared preferred_printer_id NOT NULL
            // with ON DELETE CASCADE — so removing a printer silently
            // wiped every mapping that pointed at it. Loosen the FK so the
            // row is preserved and the FK becomes NULL instead, and let
            // the observer notify the designer to reassign.
            $table->dropForeign(['preferred_printer_id']);
            $table->uuid('preferred_printer_id')->nullable()->change();
            $table->foreign('preferred_printer_id')
                ->references('id')->on('printer_provider_profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('design_product_mappings', function (Blueprint $table) {
            $table->dropForeign(['preferred_printer_id']);
            $table->uuid('preferred_printer_id')->nullable(false)->change();
            $table->foreign('preferred_printer_id')
                ->references('id')->on('printer_provider_profiles')
                ->cascadeOnDelete();
        });
    }
};
