<?php

declare(strict_types=1);

namespace Tests\Feature\Routing;

use App\Support\LocalizedUrl;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteLocaleAwareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Two-route convention: an unprefixed English route plus a
        // localized sibling mounted at /{locale}/... matching the
        // pattern established by Task 1's stub routes and verified by
        // Task 2's LocalizeHelperTest.
        Route::get('/cart', fn () => '')->name('cart.show');
        Route::get('/{locale}/cart', fn () => '')
            ->where('locale', '(en|ar|tr)')
            ->name('cart.show.localized');

        // Fluent ->name() registers the route in the collection before
        // the name is attached, so the named-lookup index needs an
        // explicit rebuild for `route('cart.show')` to find it.
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_returns_unprefixed_url_when_locale_is_en(): void
    {
        app()->setLocale('en');

        $this->assertSame(url('/cart'), LocalizedUrl::route('cart.show'));
    }

    public function test_returns_prefixed_url_when_locale_is_ar(): void
    {
        app()->setLocale('ar');

        $this->assertSame(url('/ar/cart'), LocalizedUrl::route('cart.show'));
    }

    public function test_returns_prefixed_url_when_locale_is_tr(): void
    {
        app()->setLocale('tr');

        $this->assertSame(url('/tr/cart'), LocalizedUrl::route('cart.show'));
    }
}
