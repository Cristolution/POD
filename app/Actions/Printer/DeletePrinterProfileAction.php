<?php

declare(strict_types=1);

namespace App\Actions\Printer;

use App\Models\PrinterProviderProfile;

class DeletePrinterProfileAction
{
    public function execute(PrinterProviderProfile $profile): void
    {
        abort_if($profile->productTemplates()->exists(), 409, 'Printer profile still has active templates.');
        $profile->delete();
    }
}
