<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Address\DeleteAddressAction;
use App\Actions\Address\StoreAddressAction;
use App\Actions\Address\UpdateAddressAction;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function __construct(
        private readonly StoreAddressAction $store,
        private readonly UpdateAddressAction $update,
        private readonly DeleteAddressAction $delete,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(
            $this->paginated(auth()->user()->addresses()->orderBy('id')),
        );
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $this->store->execute($request->user(), $request->validated());

        return response()->json(['data' => new AddressResource($address)], 201);
    }

    public function show(Address $address): JsonResponse
    {
        $this->authorize('view', $address);

        return response()->json(['data' => new AddressResource($address)]);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $this->authorize('update', $address);
        $updated = $this->update->execute($address, $request->validated());

        return response()->json(['data' => new AddressResource($updated)]);
    }

    public function destroy(Address $address): JsonResponse
    {
        $this->authorize('delete', $address);
        $this->delete->execute($address);

        return response()->json(null, 204);
    }
}
