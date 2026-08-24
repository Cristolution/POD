<?php

declare(strict_types=1);

namespace App\Reports\Admin;

use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevenueByDesignerReport implements Report
{
    public function run(Request $request): array
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->join('design_product_mappings', 'design_product_mappings.id', '=', 'order_items.design_product_mapping_id')
            ->join('designs', 'designs.id', '=', 'design_product_mappings.design_id')
            ->join('designer_profiles', 'designer_profiles.id', '=', 'designs.designer_id')
            ->join('users', 'users.id', '=', 'designer_profiles.user_id')
            ->where('payments.status', 'confirmed');

        if ($from = $request->date('from')) {
            $query->whereDate('payments.confirmed_at', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $query->whereDate('payments.confirmed_at', '<=', $to);
        }

        return $query
            ->groupBy('designer_profiles.id', 'users.name')
            ->orderByDesc(DB::raw('SUM(orders.total_amount)'))
            ->get([
                'designer_profiles.id as designer_id',
                'users.name as designer_name',
                DB::raw('SUM(orders.total_amount) as revenue'),
            ])
            ->map(fn ($r) => [
                'designer_id' => (string) $r->designer_id,
                'designer_name' => (string) $r->designer_name,
                'revenue' => (float) $r->revenue,
            ])
            ->all();
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['designer_id', 'designer_name', 'revenue'];
    }
}
