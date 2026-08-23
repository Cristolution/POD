<?php

declare(strict_types=1);

namespace App\Actions\Address;

use App\Models\Address;

class UpdateAddressAction
{
    /** @param array<string,mixed> $data */
    public function execute(Address $address, array $data): Address
    {
        $address->fill($data)->save();

        return $address->refresh();
    }
}
