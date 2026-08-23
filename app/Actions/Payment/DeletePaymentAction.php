<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Models\Payment;

class DeletePaymentAction
{
    public function execute(Payment $payment): void
    {
        $payment->delete();
    }
}
