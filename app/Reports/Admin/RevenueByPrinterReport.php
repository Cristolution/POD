<?php

declare(strict_types=1);

namespace App\Reports\Admin;

use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevenueByPrinterReport implements Report
{
    public function run(Request $request): array
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->join('printer_provider_profiles', 'printer_provider_profiles.id', '=', 'order_items.printer_provider_id')
            ->join('users', 'users.id', '=', 'printer_provider_profiles.user_id')
            ->where('payments.status', 'confirmed');

        if ($from = $request->date('from')) {
            $query->whereDate('payments.confirmed_at', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $query->whereDate('payments.confirmed_at', '<=', $to);
        }

        return $query
            ->groupBy('printer_provider_profiles.id', 'printer_provider_profiles.company_name', 'users.name')
            ->orderByDesc(DB::raw('SUM(orders.total_amount)'))
            ->get([
                'printer_provider_profiles.id as printer_id',
                'printer_provider_profiles.company_name',
                'users.name as printer_name',
                DB::raw('SUM(orders.total_amount) as revenue'),
            ])
            ->map(fn ($r) => [
                'printer_id' => (string) $r->printer_id,
                'printer_name' => (string) ($r->company_name ?: $r->printer_name),
                'revenue' => (float) $r->revenue,
            ])
            ->all();
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['printer_id', 'printer_name', 'revenue'];
    }
}
