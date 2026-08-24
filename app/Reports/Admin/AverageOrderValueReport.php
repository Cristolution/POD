<?php

declare(strict_types=1);

namespace App\Reports\Admin;

use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AverageOrderValueReport implements Report
{
    public function run(Request $request): array
    {
        $from = $request->date('from') ?? now()->subDays(29)->toDateString();
        $to = $request->date('to') ?? now()->toDateString();

        return DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.status', 'confirmed')
            ->whereNotNull('payments.confirmed_at')
            ->whereBetween(DB::raw('DATE(payments.confirmed_at)'), [$from, $to])
            ->groupBy(DB::raw('DATE(payments.confirmed_at)'))
            ->orderBy(DB::raw('DATE(payments.confirmed_at)'))
            ->get([
                DB::raw('DATE(payments.confirmed_at) as date'),
                DB::raw('AVG(orders.total_amount) as avg_value'),
                DB::raw('COUNT(*) as order_count'),
            ])
            ->map(fn ($r) => [
                'date' => (string) $r->date,
                'avg_value' => (float) $r->avg_value,
                'order_count' => (int) $r->order_count,
            ])
            ->all();
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['date', 'avg_value', 'order_count'];
    }
}
