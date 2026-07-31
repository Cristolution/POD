<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Laravel's built-in notifications table uses uuid
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('type'); // notification class name — not an enum, grows over time
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('read_at'); // unread count queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};