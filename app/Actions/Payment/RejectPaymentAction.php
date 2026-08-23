<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Models\Payment;
use App\Models\User;

class RejectPaymentAction
{
    public function execute(Payment $payment, User $admin): Payment
    {
        abort_unless($payment->isPending(), 409, 'Only pending payments can be rejected.');

        $payment->status = 'rejected';
        $payment->confirmed_by_admin_id = $admin->id;
        $payment->save();

        return $payment->refresh();
    }
}
