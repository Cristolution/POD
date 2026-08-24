<?php

declare(strict_types=1);

namespace App\Reports\Printer;

use App\Models\User;
use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutReport implements Report
{
    public function run(Request $request): array
    {
        $user = $request->user();
        abort_if(! $user instanceof User, 401);
        abort_if(
            ! $user->isPrinterProvider() || $user->printerProviderProfile === null,
            403,
            'Printer provider profile required.',
        );

        $printerId = $user->printerProviderProfile->id;

        $query = DB::table('order_items')
            ->where('printer_provider_id', $printerId)
            ->whereIn('status', ['handed_off'])
            ->whereNull('deleted_at');

        if ($from = $request->date('from')) {
            $query->whereDate('updated_at', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $query->whereDate('updated_at', '<=', $to);
        }

        return $query
            ->orderByDesc('updated_at')
            ->get([
                'id as order_item_id',
                'order_id',
                'status',
                'design_product_mapping_id as mapping_id',
                'quantity',
                'unit_price',
                'updated_at',
                DB::raw('unit_price * quantity as line_total'),
            ])
            ->map(fn ($r) => [
                'order_item_id' => (int) $r->order_item_id,
                'order_id' => (string) $r->order_id,
                'status' => (string) $r->status,
                'mapping_id' => (string) $r->mapping_id,
                'quantity' => (int) $r->quantity,
                'unit_price' => (float) $r->unit_price,
                'line_total' => (float) $r->line_total,
                'updated_at' => (string) $r->updated_at,
            ])
            ->all();
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['order_item_id', 'order_id', 'status', 'mapping_id', 'quantity', 'unit_price', 'line_total', 'updated_at'];
    }
}
