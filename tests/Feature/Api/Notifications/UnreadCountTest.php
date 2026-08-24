<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnreadCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_count(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        Notification::factory()->count(1)->read()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);
        Notification::factory()->count(2)->unread()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 2);
    }

    public function test_unread_count_zero_when_no_notifications(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }
}
