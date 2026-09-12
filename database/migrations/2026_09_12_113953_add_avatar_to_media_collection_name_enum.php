<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Extend the `media.collection_name` enum so user avatar media can
     * be stored alongside the existing design/product collections
     * (mockup, print_file, payment_proof, attachment).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE media MODIFY COLUMN collection_name ENUM('mockup','print_file','payment_proof','attachment','avatar') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE media MODIFY COLUMN collection_name ENUM('mockup','print_file','payment_proof','attachment') NOT NULL");
    }
};
