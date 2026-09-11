<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies a printer that they have new fulfilment work — at least one
 * order item references a product template owned by their printer profile.
 *
 * Sent once per printer per order (the PlaceOrderFromCartAction groups
 * order items by printer_provider_id and dispatches a single notification
 * per printer).
 */
class PrinterFulfilmentRequestedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, array{order_item_id: string, product: string, variant: string|null, quantity: int, unit_price: float}>  $lineItems
     */
    public function __construct(
        public readonly Order $order,
        public readonly array $lineItems,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'event' => 'printer.fulfilment.requested',
            'order_id' => $this->order->id,
            'line_items' => $this->lineItems,
            'total_items' => array_sum(array_column($this->lineItems, 'quantity')),
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $lines = collect($this->lineItems)
            ->map(fn (array $item) => sprintf(
                '· [%s] %s%s × %d ($%.2f)',
                $item['order_item_id'],
                $item['product'],
                $item['variant'] ? ' ('.$item['variant'].')' : '',
                $item['quantity'],
                $item['unit_price'],
            ))
            ->implode("\n");

        return (new MailMessage)
            ->subject("Fulfilment needed — Order {$this->order->id}")
            ->line('New order items waiting on you. Visit your fulfilment queue to update status.')
            ->line($lines);
    }
}
