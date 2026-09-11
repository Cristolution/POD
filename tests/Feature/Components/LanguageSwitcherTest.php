<?php

declare(strict_types=1);

namespace Tests\Feature\Components;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LanguageSwitcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Two-route convention so `localize('ar')` can swap to the
        // .localized sibling when the request is bound to /cart.
        Route::middleware(['localize'])->prefix('{locale?}')->where(['locale' => '(en|ar|tr)'])->group(function (): void {
            Route::get('/cart', fn () => '')->name('cart.show');
            Route::get('/{locale}/cart', fn () => '')->name('cart.show.localized');
        });

        Route::getRoutes()->refreshNameLookups();

        // Bind a default request to /cart so request()->route() resolves
        // to a named route with a `.localized` sibling — the same context
        // the switcher sees when rendered inside the site header.
        $request = Request::create('/cart', 'GET');
        $request->setRouteResolver(fn () => Route::getRoutes()->match($request));
        app()->instance('request', $request);
    }

    public function test_renders_three_locale_links(): void
    {
        $rendered = view('components.ui.language-switcher')->render();

        $this->assertStringContainsString('>EN<', $rendered);
        $this->assertStringContainsString('>AR<', $rendered);
        $this->assertStringContainsString('>TR<', $rendered);
    }

    public function test_marks_current_locale(): void
    {
        app()->setLocale('ar');

        $rendered = view('components.ui.language-switcher')->render();

        // The ar link should carry aria-current="true".
        $this->assertMatchesRegularExpression('/aria-current="true"[^>]*>AR</', $rendered);
    }

    public function test_uses_localize_for_cross_locale_urls(): void
    {
        app()->setLocale('en');

        $rendered = view('components.ui.language-switcher')->render();

        // The ar link's href should point at /ar/cart (same page, Arabic locale).
        $this->assertStringContainsString(url('/ar/cart'), $rendered);
    }
}
