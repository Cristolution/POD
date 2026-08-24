<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_single_read(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $notification = Notification::factory()->unread()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/me/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.is_unread', false);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_others_notification_read(): void
    {
        $alice = User::factory()->create(['role' => 'customer']);
        $bob = User::factory()->create(['role' => 'customer']);
        $notification = Notification::factory()->unread()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $alice->id,
        ]);

        $this->actingAs($bob, 'sanctum')
            ->patchJson("/api/me/notifications/{$notification->id}/read")
            ->assertForbidden();
    }

    public function test_mark_all_read(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        Notification::factory()->count(3)->unread()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);
        Notification::factory()->count(1)->read()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/me/notifications/mark-all-read')
            ->assertOk()
            ->assertJsonPath('marked', 3);

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(4, $user->notifications()->count());
    }

    public function test_user_can_delete_own_notification(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/me/notifications/{$notification->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_cannot_delete_others_notification(): void
    {
        $alice = User::factory()->create(['role' => 'customer']);
        $bob = User::factory()->create(['role' => 'customer']);
        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $alice->id,
        ]);

        $this->actingAs($bob, 'sanctum')
            ->deleteJson("/api/me/notifications/{$notification->id}")
            ->assertForbidden();
    }
}
