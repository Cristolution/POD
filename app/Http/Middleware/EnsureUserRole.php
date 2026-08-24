<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:admin,designer').
 * Admin always passes.
 *
 * For JSON requests, returns a 401/403 JSON body.
 * For HTML (web) requests, redirects unauthenticated visitors to the
 * login route and aborts with a 403 page when the wrong role is set,
 * so the standard Laravel error UI is rendered instead of a raw JSON blob.
 */
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('login'));
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => "Role '{$user->role}' is not permitted on this endpoint.",
                ], 403)
                : abort(403, "Role '{$user->role}' is not permitted on this endpoint.");
        }

        return $next($request);
    }
}
