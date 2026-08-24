<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_login_is_rate_limited_after_10_attempts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => "wrong{$i}@example.com",
                'password' => 'nope',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'wrong11@example.com',
            'password' => 'nope',
        ])->assertStatus(429);
    }

    public function test_register_is_rate_limited_after_10_attempts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/register', [
                'name' => 'User',
                'email' => "u{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $this->postJson('/api/auth/register', [
            'name' => 'User',
            'email' => 'u11@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(429);
    }

    public function test_api_throttle_allows_under_limit(): void
    {
        // Hit a public endpoint 5 times — well under the 30/min anon limit.
        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/api/categories')->assertOk();
        }
    }
}
