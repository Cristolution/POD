<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Route;

class LocalizedUrl
{
    /**
     * Generate a URL for the given named route with the current locale
     * prepended when the locale is non-default.
     *
     * Routes are conventionally declared in pairs: an unprefixed route
     * named `foo.bar` plus a localized sibling `foo.bar.localized`
     * mounted at `/{locale}/...`. When the current locale is non-default,
     * this method swaps to the sibling route and passes the locale as a
     * parameter, so the URL generator produces `/ar/foo` instead of
     * `/foo?locale=ar`.
     *
     * - `en` (default) → returns the unprefixed URL via the base route.
     * - `ar` / `tr` → returns `/{locale}/...` via the `.localized` sibling.
     *
     * If the projected sibling route is not registered, falls back to
     * the base route name and lets the URL generator handle it (it will
     * either honor the locale as a path parameter or append it as a
     * query string).
     *
     * Callers that need explicit target locales should use the
     * `localize()` global helper instead — this class is for plain
     * navigation links that should follow the current request's locale.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        $locale = app()->getLocale();

        if ($locale !== 'en') {
            $siblingName = $name.'.localized';

            if (Route::getRoutes()->getByName($siblingName) !== null) {
                $name = $siblingName;
            }

            $parameters['locale'] = $locale;
        }

        return app('url')->route($name, $parameters, $absolute);
    }
}
