<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Required by Illuminate\Auth\Authenticatable — SessionGuard writes
            // remember_token on successful login (Filament admin Login calls
            // this on the post-authenticate session update path).
            $table->string('remember_token', 100)->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('remember_token');
        });
    }
};
