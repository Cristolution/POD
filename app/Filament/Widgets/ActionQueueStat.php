<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Action queue — replaces the original single-stat PendingOrdersStat and
 * AbandonedCartStat with a multi-stat widget that consolidates everything
 * currently needing admin attention into one row.
 */
class ActionQueueStat extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        // Pre-shipment orders that haven't reached fulfilment yet.
        $pendingOrders = Order::query()
            ->whereIn('status', ['pending', 'paid', 'processing'])
            ->count();

        // Payments uploaded by customers that an admin still needs to
        // confirm or reject — the platform's most pressing bottleneck.
        $pendingPayments = Payment::query()
            ->where('status', 'pending')
            ->count();

        // Items currently being printed or shipped — operational health.
        $inFlightShipments = OrderItem::query()
            ->whereIn('status', ['received', 'printing', 'printed', 'handed_off'])
            ->count();

        $abandonedCount = CartItem::query()
            ->where('created_at', '<=', now()->subHours(24))
            ->count();

        // Revenue-at-risk estimate for abandoned carts. Single SQL aggregate
        // joins through mappings + variants so we don't N+1 the per-item
        // `customerPriceFor()` helper.
        $abandonedRevenue = (float) (CartItem::query()
            ->where('cart_items.created_at', '<=', now()->subHours(24))
            ->join('design_product_mappings', 'cart_items.design_product_mapping_id', '=', 'design_product_mappings.id')
            ->leftJoin('product_variants', 'cart_items.product_variant_id', '=', 'product_variants.id')
            ->selectRaw('SUM(cart_items.quantity * (design_product_mappings.final_price + COALESCE(product_variants.price_delta, 0))) as revenue')
            ->value('revenue') ?? 0);

        return [
            Stat::make('Pending orders', $pendingOrders)
                ->description('Awaiting fulfilment')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color($pendingOrders > 0 ? 'warning' : 'success'),

            Stat::make('Pending payments', $pendingPayments)
                ->description('Awaiting admin confirmation')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($pendingPayments > 0 ? 'danger' : 'success'),

            Stat::make('In-flight shipments', $inFlightShipments)
                ->description('Items being printed or shipped')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info'),

            Stat::make('Abandoned carts', $abandonedCount)
                ->description('$'.number_format($abandonedRevenue, 0).' est. revenue at risk')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color($abandonedCount > 0 ? 'warning' : 'success'),
        ];
    }
}
