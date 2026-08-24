<?php

declare(strict_types=1);

namespace App\Reports\Designer;

use App\Models\User;
use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevenueByDesignReport implements Report
{
    public function run(Request $request): array
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);
        abort_if(! $user->isDesigner() || $user->designerProfile === null, 403, 'Designer profile required.');

        $designerId = $user->designerProfile->id;

        return DB::table('designs')
            ->join('design_product_mappings', 'design_product_mappings.design_id', '=', 'designs.id')
            ->leftJoin('order_items', 'order_items.design_product_mapping_id', '=', 'design_product_mappings.id')
            ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('payments', function ($join): void {
                $join->on('payments.order_id', '=', 'orders.id')
                    ->where('payments.status', '=', 'confirmed');
            })
            ->where('designs.designer_id', $designerId)
            ->whereNull('designs.deleted_at')
            ->groupBy('designs.id', 'designs.title')
            ->orderByDesc('revenue')
            ->get([
                'designs.id as design_id',
                'designs.title as design_title',
                DB::raw('COUNT(order_items.id) as sales_count'),
                DB::raw('COALESCE(SUM(order_items.unit_price * order_items.quantity), 0) as revenue'),
            ])
            ->map(fn ($r) => [
                'design_id' => (string) $r->design_id,
                'design_title' => (string) $r->design_title,
                'sales_count' => (int) $r->sales_count,
                'revenue' => (float) $r->revenue,
            ])
            ->all();
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['design_id', 'design_title', 'sales_count', 'revenue'];
    }
}
