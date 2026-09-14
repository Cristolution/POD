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
        // MySQL stores collection_name as a typed ENUM, so adding a new
        // value requires ALTER TABLE MODIFY COLUMN. SQLite (used by the test
        // suite, :memory:) stores it as a plain VARCHAR with a check
        // constraint, so the ENUM alter syntax fails. Skip on SQLite — tests
        // accept any string value for collection_name; production MySQL still
        // gets the tightened enum.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE media MODIFY COLUMN collection_name ENUM('mockup','print_file','payment_proof','attachment','avatar') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE media MODIFY COLUMN collection_name ENUM('mockup','print_file','payment_proof','attachment') NOT NULL");
    }
};
