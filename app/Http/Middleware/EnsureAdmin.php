<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            // JSON consumers (API clients, .json URLs) get a 401.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            // Web session gate — bounce to the named `login` route if registered.
            // Phase 3 ships the actual AuthController + Blade login view; until then
            // we fail closed with a 401 instead of crashing on a missing route.
            if (Route::has('login')) {
                return redirect()->guest(route('login'));
            }

            abort(401, 'Unauthenticated.');
        }

        if (! $user->isAdmin()) {
            abort(403, 'Admin only.');
        }

        return $next($request);
    }
}
