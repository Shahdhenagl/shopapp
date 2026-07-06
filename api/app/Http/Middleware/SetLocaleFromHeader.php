<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale from the Accept-Language header.
 *
 * Only the supported content locales (en, ar) are honoured; anything else
 * falls back to the configured default. Translatable model fields then resolve
 * to this locale inside the API Resources.
 */
final class SetLocaleFromHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('app.supported_locales', ['en']);
        $fallback = (string) config('app.locale', 'en');

        $requested = $this->parse($request->header('Accept-Language'));

        $locale = in_array($requested, $supported, true) ? $requested : $fallback;

        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * Extract the primary language subtag (e.g. "ar-EG,en;q=0.8" -> "ar").
     */
    private function parse(?string $header): string
    {
        if ($header === null || $header === '') {
            return (string) config('app.locale', 'en');
        }

        $first = trim(explode(',', $header)[0]);
        $primary = strtolower(explode('-', explode(';', $first)[0])[0]);

        return $primary;
    }
}
