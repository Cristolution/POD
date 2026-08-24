<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_own_notifications(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        Notification::factory()->count(2)->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_see_others_notifications(): void
    {
        $alice = User::factory()->create(['role' => 'customer']);
        $bob = User::factory()->create(['role' => 'customer']);

        Notification::factory()->count(3)->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $alice->id,
        ]);

        // Bob sees zero of Alice's notifications.
        $this->actingAs($bob, 'sanctum')
            ->getJson('/api/me/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_unread_filter_returns_only_unread(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        Notification::factory()->count(2)->unread()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);
        Notification::factory()->count(1)->read()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
