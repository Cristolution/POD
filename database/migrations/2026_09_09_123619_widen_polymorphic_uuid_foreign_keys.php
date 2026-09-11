<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's default migrations declare polymorphic FKs (`notifiable_id`,
 * `tokenable_id`) as `unsignedBigInteger`, but our `User` model uses
 * `HasUuids`, so the real values are 36-char UUID strings. Widen both
 * columns to VARCHAR(36) so the actual data round-trips through MySQL.
 *
 * Companion to {@see 2026_09_09_122822_change_media_model_id_to_string}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notifiable_id', 36)->change();
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('tokenable_id', 36)->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('notifiable_id')->change();
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tokenable_id')->change();
        });
    }
};
