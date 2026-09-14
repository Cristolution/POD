<?php

declare(strict_types=1);

namespace App\Filament\Pages\Approvals;

use App\Actions\Order\MarkOrderDeliveredAction;
use App\Actions\Order\MarkOrderPaidAction;
use App\Actions\Order\MarkOrderProcessingAction;
use App\Actions\Order\MarkOrderShippedAction;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pending orders queue — admin one-click advance state (pending → paid →
 * processing → shipped → delivered) without opening the order.
 *
 * Reuses the same MarkOrder*Action classes the Order view page uses, so
 * customer notifications and audit log events stay consistent.
 */
class PendingOrdersPage extends Page
{
    protected string $view = 'filament.pages.approvals.orders';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Pending orders';

    protected static ?string $title = 'Pending orders';

    protected static ?string $slug = 'approvals-orders';

    protected static ?int $navigationSort = 3;

    /**
     * Eagerly loaded on mount and reloaded after every action.
     *
     * @var Collection<int, Order>
     */
    public Collection $orders;

    public function mount(): void
    {
        $this->loadQueue();
    }

    public function advanceOrder(string $orderId): void
    {
        $order = Order::findOrFail($orderId);

        $action = $this->nextActionFor($order->status);
        if ($action === null) {
            Notification::make()
                ->title('Order already at terminal state')
                ->body("Order #{$orderId} is {$order->status}; nothing to advance.")
                ->warning()
                ->send();
            $this->loadQueue();

            return;
        }

        app($action)->execute($order);

        Notification::make()
            ->title("Order advanced from {$order->status}")
            ->success()
            ->send();
        $this->loadQueue();
    }

    /**
     * Map current status → next state Action class. Returns null for
     * delivered / cancelled orders (no transition possible from here).
     */
    private function nextActionFor(string $status): ?string
    {
        return match ($status) {
            'pending' => MarkOrderPaidAction::class,
            'paid' => MarkOrderProcessingAction::class,
            'processing' => MarkOrderShippedAction::class,
            'shipped' => MarkOrderDeliveredAction::class,
            default => null,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openOrdersResource')
                ->label('All orders')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('gray')
                ->url(fn (): string => url('/admin/orders')),
        ];
    }

    private function loadQueue(): void
    {
        $this->orders = Order::query()
            ->whereIn('status', ['pending', 'paid', 'processing', 'shipped'])
            ->with(['customer'])
            ->latest('created_at')
            ->get();
    }
}
