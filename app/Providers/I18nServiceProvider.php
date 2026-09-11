<?php

declare(strict_types=1);

namespace App\Providers {
    use Illuminate\Support\ServiceProvider;

    class I18nServiceProvider extends ServiceProvider
    {
        public function register(): void
        {
            //
        }

        public function boot(): void
        {
            $this->registerLocalizeHelper();
        }

        /**
         * The `localize()` function itself lives in the global namespace block
         * below. This method is just the boot-time hook that triggers its
         * declaration (guarded by function_exists so it only happens once per
         * PHP process).
         */
        private function registerLocalizeHelper(): void
        {
            // Intentionally empty — the global `localize()` function below
            // is declared once when this file is first autoloaded. The
            // guard in the global block prevents redeclaration on repeated
            // boots within the same PHP process (e.g. test runs).
        }
    }
}

namespace {
    use Illuminate\Support\Facades\Route;

    /**
     * Build the URL of the current route in the requested locale.
     *
     * - `localize(null)` or `localize('en')` returns the English unprefixed URL.
     * - `localize('ar'|'tr')` returns the `/ar/...` or `/tr/...` prefixed URL.
     * - When no current route is bound, falls back to `url('/')`.
     * - Route parameters (other than `locale`) are preserved on the rewritten URL.
     *
     * Routes are conventionally declared in pairs: an unprefixed route named
     * `foo.bar` plus a localized sibling `foo.bar.localized` mounted at
     * `/{locale}/...`. When switching locales, the helper swaps to the
     * sibling route. If only one variant is registered, the helper falls
     * back to the current route name (parameters are still locale-correct).
     *
     * Note: this helper builds URLs explicitly via `url(route(...))`. Phase
     * A Task 3 introduces `App\Support\LocalizedUrl::route()` for plain
     * `route()` calls — that class lets existing Blade `route('foo')` calls
     * auto-prepend the current locale. This helper exists for the cases
     * where the caller needs an explicit target locale.
     */
    if (! function_exists('localize')) {
        function localize(?string $locale = null): string
        {
            $request = request();
            $route = $request->route();

            if ($route === null) {
                return url('/');
            }

            $targetLocale = $locale ?? 'en';

            $routeName = $route->getName();
            $baseName = str_ends_with((string) $routeName, '.localized')
                ? substr((string) $routeName, 0, -10)
                : (string) $routeName;

            $targetRouteName = $targetLocale === 'en'
                ? $baseName
                : $baseName.'.localized';

            // Fall back to the current route name if the projected sibling
            // does not exist — keeps the helper safe on routes that were
            // only registered as the unprefixed or only as the localized
            // variant.
            if (
                $targetRouteName !== $routeName
                && Route::getRoutes()->getByName($targetRouteName) === null
            ) {
                $targetRouteName = (string) $routeName;
            }

            $parameters = $route->parameters();
            unset($parameters['locale']);

            if ($targetLocale !== 'en') {
                $parameters['locale'] = $targetLocale;
            }

            return url(route($targetRouteName, $parameters, false));
        }
    }
}
