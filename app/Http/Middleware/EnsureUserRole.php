<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:admin,designer').
 * Admin always passes.
 */
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => "Role '{$user->role}' is not permitted on this endpoint.",
            ], 403);
        }

        return $next($request);
    }
}
