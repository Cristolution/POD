<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class LocalizeRequests
{
    /** @var list<string> */
    private const SUPPORTED_LOCALES = ['en', 'ar', 'tr'];

    private const COOKIE_NAME = 'pod_locale';

    private const COOKIE_TTL_MINUTES = 60 * 24 * 365;

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);

        // Only persist the cookie when the URL prefix was the source — that
        // is the explicit signal that the visitor actively chose a locale.
        // Falling back from cookie/Accept-Language should not overwrite the
        // cookie with a value the visitor never confirmed.
        $urlLocale = $request->route('locale');

        if ($urlLocale !== null && in_array($locale, self::SUPPORTED_LOCALES, true)) {
            Cookie::queue(self::COOKIE_NAME, $locale, self::COOKIE_TTL_MINUTES);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $urlLocale = $request->route('locale');

        if ($urlLocale !== null && in_array($urlLocale, self::SUPPORTED_LOCALES, true)) {
            return $urlLocale;
        }

        $cookieLocale = $request->cookie(self::COOKIE_NAME);

        if ($cookieLocale !== null && in_array($cookieLocale, self::SUPPORTED_LOCALES, true)) {
            return $cookieLocale;
        }

        $acceptLanguage = $request->header('Accept-Language');

        if ($acceptLanguage !== null) {
            $preferred = $this->parseAcceptLanguage($acceptLanguage);

            if ($preferred !== null) {
                return $preferred;
            }
        }

        return (string) config('app.locale', 'en');
    }

    /**
     * Parse an RFC-7231 Accept-Language header into the visitor's preferred
     * supported locale. Returns the highest-q supported entry, falling back
     * to the first supported entry that appears without a q-value.
     */
    private function parseAcceptLanguage(string $header): ?string
    {
        $entries = [];

        foreach (explode(',', $header) as $part) {
            $bits = explode(';', trim($part));
            $rawCode = strtolower(trim($bits[0]));
            // Strip region/variant suffix: "en-US" -> "en"
            $code = explode('-', $rawCode)[0];

            if ($code === '') {
                continue;
            }

            $q = 1.0;

            if (isset($bits[1]) && preg_match('/q\s*=\s*([\d.]+)/', $bits[1], $match) === 1) {
                $q = (float) $match[1];
            }

            // First occurrence wins on ties — preserves the visitor's
            // declared order when q-values are equal.
            if (! isset($entries[$code])) {
                $entries[$code] = $q;
            }
        }

        arsort($entries);

        foreach (array_keys($entries) as $code) {
            if (in_array($code, self::SUPPORTED_LOCALES, true)) {
                return $code;
            }
        }

        return null;
    }
}
