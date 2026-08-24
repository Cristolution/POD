<?php

declare(strict_types=1);

namespace App\Reports\Admin;

use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerLtvReport implements Report
{
    public function run(Request $request): array
    {
        return DB::table('orders')
            ->leftJoin('payments', function ($join): void {
                $join->on('payments.order_id', '=', 'orders.id')
                    ->where('payments.status', '=', 'confirmed');
            })
            ->join('users', 'users.id', '=', 'orders.customer_id')
            ->whereNull('orders.deleted_at')
            ->where('users.role', 'customer')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_spent')
            ->limit(50)
            ->get([
                'users.id as customer_id',
                'users.name as customer_name',
                'users.email as customer_email',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(CASE WHEN payments.id IS NOT NULL THEN orders.total_amount ELSE 0 END), 0) as total_spent'),
            ])
            ->map(fn ($r) => [
                'customer_id' => (string) $r->customer_id,
                'customer_name' => (string) $r->customer_name,
                'customer_email' => (string) $r->customer_email,
                'total_orders' => (int) $r->total_orders,
                'total_spent' => (float) $r->total_spent,
            ])
            ->all();
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['customer_id', 'customer_name', 'customer_email', 'total_orders', 'total_spent'];
    }
}
