<?php

declare(strict_types=1);

namespace App\Actions\Printer;

use App\Models\PrinterProviderProfile;

class UpdatePrinterProfileAction
{
    /** @param array<string,mixed> $data */
    public function execute(PrinterProviderProfile $profile, array $data): PrinterProviderProfile
    {
        $profile->fill($data)->save();

        return $profile->refresh();
    }
}
