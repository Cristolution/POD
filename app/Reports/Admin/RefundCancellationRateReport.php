<?php

declare(strict_types=1);

namespace App\Reports\Admin;

use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundCancellationRateReport implements Report
{
    public function run(Request $request): array
    {
        $from = $request->date('from') ?? now()->subDays(29)->toDateString();
        $to = $request->date('to') ?? now()->toDateString();

        $orderTotal = (int) DB::table('orders')
            ->whereNull('deleted_at')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->count();

        $orderCancelled = (int) DB::table('orders')
            ->whereNull('deleted_at')
            ->where('status', 'cancelled')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->count();

        $paymentTotal = (int) DB::table('payments')
            ->whereNull('deleted_at')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->count();

        $paymentRejected = (int) DB::table('payments')
            ->whereNull('deleted_at')
            ->where('status', 'rejected')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->count();

        return [
            [
                'metric' => 'order_total',
                'count' => $orderTotal,
                'rate' => null,
                'from' => $from,
                'to' => $to,
            ],
            [
                'metric' => 'order_cancelled',
                'count' => $orderCancelled,
                'rate' => $orderTotal > 0 ? round($orderCancelled / $orderTotal, 4) : 0.0,
                'from' => $from,
                'to' => $to,
            ],
            [
                'metric' => 'payment_total',
                'count' => $paymentTotal,
                'rate' => null,
                'from' => $from,
                'to' => $to,
            ],
            [
                'metric' => 'payment_rejected',
                'count' => $paymentRejected,
                'rate' => $paymentTotal > 0 ? round($paymentRejected / $paymentTotal, 4) : 0.0,
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['metric', 'count', 'rate', 'from', 'to'];
    }
}
