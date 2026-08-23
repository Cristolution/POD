<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Catalog\DeleteDesignProductMappingAction;
use App\Actions\Catalog\StoreDesignProductMappingAction;
use App\Actions\Catalog\UpdateDesignProductMappingAction;
use App\Http\Requests\Catalog\StoreDesignProductMappingRequest;
use App\Http\Requests\Catalog\UpdateDesignProductMappingRequest;
use App\Http\Resources\DesignProductMappingResource;
use App\Models\DesignProductMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignProductMappingController extends Controller
{
    public function __construct(
        private readonly StoreDesignProductMappingAction $store,
        private readonly UpdateDesignProductMappingAction $update,
        private readonly DeleteDesignProductMappingAction $delete,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DesignProductMapping::query()->with(['design', 'productTemplate', 'preferredPrinter']);

        if ($designId = $request->string('design_id')->value()) {
            $query->where('design_id', $designId);
        }

        if ($templateId = $request->string('product_template_id')->value()) {
            $query->where('product_template_id', $templateId);
        }

        if ($printerId = $request->string('printer_id')->value()) {
            $query->where('preferred_printer_id', $printerId);
        }

        if ($request->has('is_active')) {
            // `is_active` is reserved on the URL contract for the public index,
            // but no physical column exists yet — accepted and ignored so
            // existing clients do not 500 when they pass it.
        }

        return response()->json($this->paginated($query));
    }

    public function show(DesignProductMapping $mapping): JsonResponse
    {
        return response()->json([
            'data' => new DesignProductMappingResource(
                $mapping->load(['design', 'productTemplate', 'preferredPrinter']),
            ),
        ]);
    }

    public function store(StoreDesignProductMappingRequest $request): JsonResponse
    {
        $mapping = $this->store->execute($request->user(), $request->validated());

        return response()->json(['data' => new DesignProductMappingResource($mapping)], 201);
    }

    public function update(UpdateDesignProductMappingRequest $request, DesignProductMapping $mapping): JsonResponse
    {
        $this->authorize('update', $mapping);
        $updated = $this->update->execute($mapping, $request->validated());

        return response()->json(['data' => new DesignProductMappingResource($updated)]);
    }

    public function destroy(DesignProductMapping $mapping): JsonResponse
    {
        $this->authorize('delete', $mapping);
        $this->delete->execute($mapping);

        return response()->json(null, 204);
    }
}
