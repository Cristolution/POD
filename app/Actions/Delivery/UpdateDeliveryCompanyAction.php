<?php

declare(strict_types=1);

namespace App\Actions\Delivery;

use App\Models\DeliveryCompany;

class UpdateDeliveryCompanyAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(DeliveryCompany $company, array $data): DeliveryCompany
    {
        $company->update($data);

        return $company->refresh();
    }
}
