<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel auth/session support tables.
 *
 * Required because:
 *   - SESSION_DRIVER=database  → sessions table
 *   - password reset flow      → password_reset_tokens table
 *
 * The POD schema migration creates its own `users` table without these
 * auxiliary tables (and without `remember_token` / `email_verified_at`).
 * If/when those Laravel-default auth features are wired up, add the
 * missing columns in a follow-up migration rather than editing this one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
    }
};