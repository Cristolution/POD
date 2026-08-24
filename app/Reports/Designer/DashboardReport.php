<?php

declare(strict_types=1);

namespace App\Reports\Designer;

use App\Models\User;
use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardReport implements Report
{
    public function run(Request $request): array
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);
        abort_if(! $user->isDesigner() || $user->designerProfile === null, 403, 'Designer profile required.');

        $designerId = $user->designerProfile->id;

        $publishedDesigns = (int) DB::table('designs')
            ->where('designer_id', $designerId)
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->count();

        $totalSales = (int) DB::table('order_items')
            ->join('design_product_mappings', 'design_product_mappings.id', '=', 'order_items.design_product_mapping_id')
            ->join('designs', 'designs.id', '=', 'design_product_mappings.design_id')
            ->where('designs.designer_id', $designerId)
            ->whereNull('order_items.deleted_at')
            ->sum('order_items.quantity');

        $totalRevenue = (float) DB::table('order_items')
            ->join('design_product_mappings', 'design_product_mappings.id', '=', 'order_items.design_product_mapping_id')
            ->join('designs', 'designs.id', '=', 'design_product_mappings.design_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->where('designs.designer_id', $designerId)
            ->where('payments.status', 'confirmed')
            ->sum(DB::raw('order_items.unit_price * order_items.quantity'));

        return [
            [
                'metric' => 'published_designs',
                'value' => $publishedDesigns,
            ],
            [
                'metric' => 'total_sales',
                'value' => $totalSales,
            ],
            [
                'metric' => 'total_revenue',
                'value' => $totalRevenue,
            ],
        ];
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['metric', 'value'];
    }
}
