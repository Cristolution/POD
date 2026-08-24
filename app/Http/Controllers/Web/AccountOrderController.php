<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountOrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.account.orders.index', [
            'orders' => $request->user()->orders()
                ->with(['shippingAddress', 'payments'])
                ->orderByDesc('created_at')
                ->paginate(10),
        ]);
    }
}
