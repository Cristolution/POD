<?php

declare(strict_types=1);

namespace App\Actions\Printer;

use App\Models\PrinterProviderProfile;
use App\Models\User;

class StorePrinterProfileAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): PrinterProviderProfile
    {
        return $user->printerProviderProfile()->create($data);
    }
}
