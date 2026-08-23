<?php

declare(strict_types=1);

namespace App\Actions\Address;

use App\Models\Address;
use App\Models\User;

class StoreAddressAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): Address
    {
        return $user->addresses()->create($data);
    }
}
