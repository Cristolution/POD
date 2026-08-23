<?php

declare(strict_types=1);

namespace App\Actions\Cart;

use App\Models\User;

class ClearCartAction
{
    public function execute(User $user): void
    {
        $user->cartItems()->delete();
    }
}
