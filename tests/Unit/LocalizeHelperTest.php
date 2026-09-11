<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\LocalizeRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocalizeHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/foo', fn () => '')->name('foo.test');
        Route::get('/{locale}/foo', fn () => '')
            ->where('locale', '(en|ar|tr)')
            ->name('foo.test.localized');

        Route::get('/bar/{id}', fn () => '')->name('bar.test');
        Route::get('/{locale}/bar/{id}', fn () => '')
            ->where('locale', '(en|ar|tr)')
            ->name('bar.test.localized');

        // Fluent ->name() registers the route in the collection before the
        // name is attached, so the named-lookup index needs an explicit
        // rebuild for `route('foo.test')` to find it.
        Route::getRoutes()->refreshNameLookups();
    }

    private function mountRequest(string $path, string $locale): void
    {
        $request = Request::create($path, 'GET');
        $request->setRouteResolver(fn () => Route::getRoutes()->match($request));

        app()->instance('request', $request);
        app()->setLocale($locale);
    }

    public function test_returns_english_unprefixed_when_locale_is_null(): void
    {
        $this->app['router']->aliasMiddleware('localize', LocalizeRequests::class);

        $this->mountRequest('/foo', 'en');

        $this->assertSame(url('/foo'), localize(null));
    }

    public function test_strips_prefix_when_switching_to_en_from_another_locale(): void
    {
        $this->app['router']->aliasMiddleware('localize', LocalizeRequests::class);

        $this->mountRequest('/ar/foo', 'ar');

        $this->assertSame(url('/foo'), localize('en'));
    }

    public function test_swaps_prefix_when_switching_between_non_default_locales(): void
    {
        $this->app['router']->aliasMiddleware('localize', LocalizeRequests::class);

        $this->mountRequest('/ar/foo', 'ar');

        $this->assertSame(url('/tr/foo'), localize('tr'));
    }

    public function test_returns_arabic_for_routes_with_parameters(): void
    {
        $this->app['router']->aliasMiddleware('localize', LocalizeRequests::class);

        $this->mountRequest('/bar/42', 'en');

        $this->assertSame(url('/ar/bar/42'), localize('ar'));
    }
}
