<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Notifications;

use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_broadcast_to_multiple_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targets = User::factory()->count(3)->create(['role' => 'customer']);
        $targetIds = $targets->pluck('id')->all();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/notifications', [
                'type' => 'platform.announcement',
                'message' => 'Scheduled maintenance this Saturday.',
                'target_user_ids' => $targetIds,
            ]);

        $response->assertCreated()
            ->assertJsonPath('sent', 3);

        $this->assertDatabaseCount('notifications', 3);

        foreach ($targets as $target) {
            $this->assertDatabaseHas('notifications', [
                'notifiable_type' => User::class,
                'notifiable_id' => $target->id,
                'type' => AdminBroadcastNotification::class,
            ]);

            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => $target->id,
                'type' => AdminBroadcastNotification::class,
            ]);
        }
    }

    public function test_non_admin_cannot_broadcast(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/notifications', [
                'type' => 'platform.announcement',
                'message' => 'Hi everyone',
                'target_user_ids' => [$other->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_anonymous_cannot_broadcast(): void
    {
        $target = User::factory()->create(['role' => 'customer']);

        $this->postJson('/api/admin/notifications', [
            'type' => 'platform.announcement',
            'message' => 'Hi',
            'target_user_ids' => [$target->id],
        ])->assertUnauthorized();
    }
}
