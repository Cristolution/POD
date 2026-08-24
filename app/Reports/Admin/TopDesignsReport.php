<?php

declare(strict_types=1);

namespace App\Reports\Admin;

use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TopDesignsReport implements Report
{
    public function run(Request $request): array
    {
        return DB::table('order_items')
            ->join('design_product_mappings', 'design_product_mappings.id', '=', 'order_items.design_product_mapping_id')
            ->join('designs', 'designs.id', '=', 'design_product_mappings.design_id')
            ->whereNull('order_items.deleted_at')
            ->groupBy('designs.id', 'designs.title')
            ->orderByDesc('sales_count')
            ->limit(20)
            ->get([
                'designs.id as design_id',
                'designs.title as design_title',
                DB::raw('COUNT(*) as sales_count'),
            ])
            ->map(fn ($r) => [
                'design_id' => (string) $r->design_id,
                'design_title' => (string) $r->design_title,
                'sales_count' => (int) $r->sales_count,
            ])
            ->all();
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['design_id', 'design_title', 'sales_count'];
    }
}
