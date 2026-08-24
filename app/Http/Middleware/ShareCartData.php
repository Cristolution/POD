<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareCartData
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        View::share('unreadCount', $user?->unreadNotifications()->count() ?? 0);
        View::share('cartCount', $user?->cartItems()->count() ?? 0);

        return $next($request);
    }
}
