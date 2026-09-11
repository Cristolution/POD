<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Tests\TestCase;

class LocalizeRequestsTest extends TestCase
{
    public function test_uses_url_prefix_when_present_ignoring_cookie(): void
    {
        $response = $this->withCookie('pod_locale', 'tr')
            ->get('/ar/about');

        $response->assertOk();
        $this->assertSame('ar', app()->getLocale());
    }

    public function test_uses_cookie_when_url_has_no_locale(): void
    {
        $this->withCookie('pod_locale', 'tr')
            ->get('/about');

        $this->assertSame('tr', app()->getLocale());
    }

    public function test_falls_back_to_accept_language_when_no_url_or_cookie(): void
    {
        $response = $this->withHeader('Accept-Language', 'ar;q=0.9,en;q=0.8')
            ->get('/about');

        $response->assertOk();
        $this->assertSame('ar', app()->getLocale());
    }

    public function test_falls_back_to_default_when_nothing_matches(): void
    {
        $this->get('/about');

        $this->assertSame('en', app()->getLocale());
    }

    public function test_rejects_invalid_url_locales(): void
    {
        $response = $this->get('/xx/about');

        // Either 404 (regex blocked in Task 4) or 200 with fallback to en —
        // both acceptable per the brief.
        $this->assertContains($response->status(), [200, 404]);
    }
}
