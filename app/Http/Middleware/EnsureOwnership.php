<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('owner:design') — resolves the route binding
 * whose parameter name matches the model short-name and confirms the
 * authenticated user is the owner. Admin always passes.
 */
class EnsureOwnership
{
    public function handle(Request $request, Closure $next, string $modelShortName): Response
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($user->isAdmin()) {
            return $next($request);
        }

        $routeKey = strtolower($modelShortName);
        /** @var Model|null $model */
        $model = $request->route($routeKey);

        if ($model === null) {
            return response()->json(['message' => "Route binding '{$routeKey}' missing."], 500);
        }

        $ownerColumn = match (true) {
            property_exists($model, 'user_id') => 'user_id',
            property_exists($model, 'designer_id') => 'designer_id',
            property_exists($model, 'printer_provider_id') => 'printer_provider_id',
            default => null,
        };

        if ($ownerColumn === null || (string) $model->{$ownerColumn} !== (string) $user->id) {
            return response()->json(['message' => 'You do not own this resource.'], 403);
        }

        return $next($request);
    }
}
