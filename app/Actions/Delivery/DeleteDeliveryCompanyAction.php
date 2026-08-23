<?php

declare(strict_types=1);

namespace App\Actions\Delivery;

use App\Models\DeliveryCompany;
use Illuminate\Database\QueryException;

class DeleteDeliveryCompanyAction
{
    public function execute(DeliveryCompany $company): void
    {
        try {
            $company->delete();
        } catch (QueryException $e) {
            // Foreign key protect on shipments.delivery_company_id.
            abort(409, 'Cannot delete a delivery company that is in use by shipments.');
        }
    }
}
