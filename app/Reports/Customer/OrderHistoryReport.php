<?php

declare(strict_types=1);

namespace App\Reports\Customer;

use App\Models\User;
use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderHistoryReport implements Report
{
    public function run(Request $request): array
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);
        abort_if(! $user->isCustomer(), 403, 'Customer role required.');

        $perPage = max(1, min((int) $request->integer('per_page', 25), 100));
        $page = max(1, (int) $request->integer('page', 1));

        $base = DB::table('orders')
            ->leftJoin('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.customer_id', $user->id)
            ->whereNull('orders.deleted_at')
            ->groupBy('orders.id', 'orders.status', 'orders.total_amount', 'orders.created_at')
            ->orderByDesc('orders.created_at')
            ->get([
                'orders.id as order_id',
                'orders.status',
                'orders.total_amount',
                'orders.created_at',
                DB::raw('COUNT(order_items.id) as items_count'),
            ])
            ->map(fn ($r) => [
                'order_id' => (string) $r->order_id,
                'status' => (string) $r->status,
                'total_amount' => (float) $r->total_amount,
                'items_count' => (int) $r->items_count,
                'created_at' => (string) $r->created_at,
            ]);

        $total = $base->count();
        $rows = $base->forPage($page, $perPage)->values()->all();

        $meta = [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) ceil($total / max($perPage, 1)),
        ];

        return [
            'data' => $rows,
            'meta' => $meta,
        ];
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['order_id', 'status', 'total_amount', 'items_count', 'created_at'];
    }
}
