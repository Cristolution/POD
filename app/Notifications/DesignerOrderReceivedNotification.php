<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies a designer that one of their designs was purchased.
 *
 * Sent once per designer per order (the PlaceOrderFromCartAction groups
 * the order items by designer_id and dispatches a single notification per
 * designer, so a multi-designer cart doesn't spam anyone).
 */
class DesignerOrderReceivedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, array{title: string, quantity: int, unit_price: float}>  $lineItems
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
            'event' => 'designer.order.received',
            'order_id' => $this->order->id,
            'line_items' => $this->lineItems,
            'total_items' => array_sum(array_column($this->lineItems, 'quantity')),
        ];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $lines = collect($this->lineItems)
            ->map(fn (array $item) => "· {$item['title']} × {$item['quantity']} (\${$item['unit_price']})")
            ->implode("\n");

        return (new MailMessage)
            ->subject("New order for your design(s) — Order {$this->order->id}")
            ->line("One of your designs was just purchased. Order total: \${$this->order->total_amount}.")
            ->line($lines);
    }
}
