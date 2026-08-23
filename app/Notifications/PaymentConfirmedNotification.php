<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Payment $payment) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment confirmed for order {$this->payment->order_id}")
            ->line("Your payment for order {$this->payment->order_id} has been confirmed.")
            ->line('Production will begin shortly.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'event' => 'payment.confirmed',
            'payment_id' => $this->payment->id,
            'order_id' => $this->payment->order_id,
        ];
    }
}
