<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('owner:design') — resolves the route binding
 * whose parameter name matches the model short-name and confirms the
 * authenticated user is the owner. Admin always passes.
 *
 * Accepts either 'address' (short name) or 'App\Models\Address' (FQCN)
 * to keep the call sites readable. The middleware resolves the binding
 * itself so it works whether or not SubstituteBindings has run.
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

        $modelClass = Str::contains($modelShortName, '\\')
            ? $modelShortName
            : 'App\\Models\\'.Str::studly($modelShortName);

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return response()->json(['message' => "Unknown owner model '{$modelShortName}'."], 500);
        }

        $routeKey = Str::camel(class_basename($modelClass));
        $rawId = $request->route($routeKey);

        if ($rawId === null) {
            return response()->json(['message' => "Route binding '{$routeKey}' missing."], 500);
        }

        /** @var Model|null $model */
        $model = $modelClass::find($rawId);

        if ($model === null) {
            return response()->json(['message' => 'Resource not found.'], 404);
        }

        $ownerColumn = match (true) {
            array_key_exists('user_id', $model->getAttributes()) => 'user_id',
            array_key_exists('designer_id', $model->getAttributes()) => 'designer_id',
            array_key_exists('printer_provider_id', $model->getAttributes()) => 'printer_provider_id',
            default => null,
        };

        if ($ownerColumn === null || (string) $model->{$ownerColumn} !== (string) $user->id) {
            return response()->json(['message' => 'You do not own this resource.'], 403);
        }

        return $next($request);
    }
}
