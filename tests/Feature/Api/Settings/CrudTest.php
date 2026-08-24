<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/settings', [
                'key' => 'platform.fee',
                'value' => '0.10',
            ])
            ->assertCreated()
            ->assertJsonPath('data.key', 'platform.fee')
            ->assertJsonPath('data.value', '0.10');

        $this->assertDatabaseHas('settings', [
            'key' => 'platform.fee',
            'value' => '0.10',
        ]);
    }

    public function test_admin_can_update_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = Setting::factory()->create([
            'key' => 'platform.fee',
            'value' => '0.10',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/settings/{$setting->id}", [
                'value' => '0.15',
            ])
            ->assertOk()
            ->assertJsonPath('data.value', '0.15');

        $this->assertDatabaseHas('settings', [
            'id' => $setting->id,
            'key' => 'platform.fee',
            'value' => '0.15',
        ]);
    }

    public function test_customer_cannot_create(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/settings', [
                'key' => 'platform.fee',
                'value' => '0.10',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_customer_cannot_update(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $setting = Setting::factory()->create(['key' => 'platform.fee']);

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/admin/settings/{$setting->id}", [
                'value' => '0.99',
            ])
            ->assertForbidden();
    }

    public function test_duplicate_key_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::factory()->create(['key' => 'platform.fee']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/settings', [
                'key' => 'platform.fee',
                'value' => '0.20',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('key');
    }
}
