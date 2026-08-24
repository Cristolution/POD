<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Settings;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_can_list_settings(): void
    {
        Setting::factory()->count(3)->create();

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_anonymous_can_show_public_setting(): void
    {
        $setting = Setting::factory()->create();

        $this->getJson("/api/settings/{$setting->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $setting->id)
            ->assertJsonPath('data.key', $setting->key);
    }

    public function test_settings_index_supports_key_prefix_filter(): void
    {
        Setting::factory()->create(['key' => 'platform.fee']);
        Setting::factory()->create(['key' => 'marketing.tagline']);
        Setting::factory()->create(['key' => 'platform.maintenance']);

        $this->getJson('/api/settings?key=platform.')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
