<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        // Admins only — non-admins use /me/payments.
        return $user->isAdmin();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $payment->order?->customer_id === $user->id;
    }

    public function create(User $user, ?Order $order = null): bool
    {
        // Admins short-circuit via before(); non-admins fall through here.
        return false;
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
