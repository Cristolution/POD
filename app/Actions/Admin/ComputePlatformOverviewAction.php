<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\CartItem;
use App\Models\Design;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductTemplate;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComputePlatformOverviewAction
{
    /**
     * Compute the platform-wide KPI snapshot for the admin dashboard.
     *
     * Cached for 60 seconds — a single shared read, cheap to recompute, but
     * expensive enough that polling dashboards should not hit the DB each tick.
     *
     * @return array<string, int|float>
     */
    public function execute(): array
    {
        return Cache::remember('platform_overview_v1', 60, function (): array {
            return [
                'total_customers' => User::where('role', 'customer')->whereNull('deleted_at')->count(),
                'total_designers' => User::where('role', 'designer')->whereNull('deleted_at')->count(),
                'total_printers' => User::where('role', 'printer_provider')->whereNull('deleted_at')->count(),
                'total_published_designs' => Design::where('status', 'published')->whereNull('deleted_at')->count(),
                'total_active_templates' => ProductTemplate::whereNull('deleted_at')->count(),
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'orders_this_month' => Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'revenue_today' => (float) DB::table('payments')
                    ->join('orders', 'orders.id', '=', 'payments.order_id')
                    ->where('payments.status', 'confirmed')
                    ->whereDate('payments.confirmed_at', today())
                    ->sum('orders.total_amount'),
                'revenue_this_month' => (float) DB::table('payments')
                    ->join('orders', 'orders.id', '=', 'payments.order_id')
                    ->where('payments.status', 'confirmed')
                    ->whereMonth('payments.confirmed_at', now()->month)
                    ->whereYear('payments.confirmed_at', now()->year)
                    ->sum('orders.total_amount'),
                'pending_payments' => Payment::where('status', 'pending')->count(),
                'pending_order_items' => OrderItem::where('status', 'pending')->whereNull('deleted_at')->count(),
                'in_flight_shipments' => Shipment::whereIn('status', ['pending', 'shipped'])->count(),
                'abandoned_carts_24h' => CartItem::where('updated_at', '>=', now()->subDay())->distinct()->count('user_id'),
            ];
        });
    }
}
