<?php

declare(strict_types=1);

namespace App\Actions\Delivery;

use App\Models\DeliveryCompany;

class StoreDeliveryCompanyAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): DeliveryCompany
    {
        $company = DeliveryCompany::create($data);

        return $company->refresh();
    }
}
