<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentConfirmedNotification;

class ConfirmPaymentAction
{
    public function execute(Payment $payment, User $admin): Payment
    {
        abort_unless($payment->isPending(), 409, 'Only pending payments can be confirmed.');

        $payment->status = 'confirmed';
        $payment->confirmed_by_admin_id = $admin->id;
        $payment->confirmed_at = now();
        $payment->save();

        $payment = $payment->refresh();

        $payment->order?->customer?->notify(new PaymentConfirmedNotification($payment));

        return $payment;
    }
}
