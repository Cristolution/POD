<?php

declare(strict_types=1);

namespace App\Reports\Printer;

use App\Models\User;
use App\Reports\Contracts\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkQueueReport implements Report
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

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.printer_provider_id', $printerId)
            ->whereIn('order_items.status', ['pending', 'received', 'printing', 'printed'])
            ->whereNull('order_items.deleted_at')
            ->orderBy('orders.created_at')
            ->get([
                'order_items.id as order_item_id',
                'order_items.order_id',
                'order_items.status',
                'order_items.design_product_mapping_id as mapping_id',
                'order_items.quantity',
                'orders.created_at as placed_at',
            ])
            ->map(fn ($r) => [
                'order_item_id' => (int) $r->order_item_id,
                'order_id' => (string) $r->order_id,
                'status' => (string) $r->status,
                'mapping_id' => (string) $r->mapping_id,
                'quantity' => (int) $r->quantity,
                'placed_at' => (string) $r->placed_at,
            ])
            ->all();
    }

    /** @return array<int,string> */
    public function csvHeaders(): array
    {
        return ['order_item_id', 'order_id', 'status', 'mapping_id', 'quantity', 'placed_at'];
    }
}
