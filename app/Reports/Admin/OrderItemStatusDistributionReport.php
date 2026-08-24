<?php

declare(strict_types=1);

namespace App\Reports\Admin;

use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderItemStatusDistributionReport implements Report
{
    public function run(Request $request): array
    {
        return DB::table('order_items')
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get(['status', DB::raw('COUNT(*) as count')])
            ->map(fn ($r) => [
                'status' => (string) $r->status,
                'count' => (int) $r->count,
            ])
            ->all();
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['status', 'count'];
    }
}
