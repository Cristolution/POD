<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Delivery\DeleteDeliveryCompanyAction;
use App\Actions\Delivery\StoreDeliveryCompanyAction;
use App\Actions\Delivery\UpdateDeliveryCompanyAction;
use App\Http\Requests\Delivery\StoreDeliveryCompanyRequest;
use App\Http\Requests\Delivery\UpdateDeliveryCompanyRequest;
use App\Http\Resources\DeliveryCompanyResource;
use App\Models\DeliveryCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryCompanyController extends Controller
{
    public function __construct(
        private readonly StoreDeliveryCompanyAction $store,
        private readonly UpdateDeliveryCompanyAction $update,
        private readonly DeleteDeliveryCompanyAction $delete,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DeliveryCompany::query()->orderBy('name');

        return response()->json($this->paginated($query));
    }

    public function show(DeliveryCompany $company): JsonResponse
    {
        $this->authorize('view', $company);

        return response()->json(['data' => new DeliveryCompanyResource($company)]);
    }

    public function store(StoreDeliveryCompanyRequest $request): JsonResponse
    {
        $company = $this->store->execute($request->validated());

        return response()->json(['data' => new DeliveryCompanyResource($company)], 201);
    }

    public function update(UpdateDeliveryCompanyRequest $request, DeliveryCompany $company): JsonResponse
    {
        $updated = $this->update->execute($company, $request->validated());

        return response()->json(['data' => new DeliveryCompanyResource($updated)]);
    }

    public function destroy(DeliveryCompany $company): JsonResponse
    {
        $this->delete->execute($company);

        return response()->json(null, 204);
    }
}
