<?php

declare(strict_types=1);

namespace App\Reports\Admin;

use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevenueByDayReport implements Report
{
    public function run(Request $request): array
    {
        $to = $request->date('to') ?? now()->toDateString();
        $from = $request->date('from') ?? now()->subDays(29)->toDateString();

        $rows = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.status', 'confirmed')
            ->whereNotNull('payments.confirmed_at')
            ->whereBetween(DB::raw('DATE(payments.confirmed_at)'), [$from, $to])
            ->groupBy(DB::raw('DATE(payments.confirmed_at)'))
            ->orderBy(DB::raw('DATE(payments.confirmed_at)'))
            ->get([
                DB::raw('DATE(payments.confirmed_at) as date'),
                DB::raw('SUM(orders.total_amount) as revenue'),
                DB::raw('COUNT(*) as payment_count'),
            ])
            ->map(fn ($r) => [
                'date' => (string) $r->date,
                'revenue' => (float) $r->revenue,
                'payment_count' => (int) $r->payment_count,
            ])
            ->all();

        return $rows;
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['date', 'revenue', 'payment_count'];
    }
}
