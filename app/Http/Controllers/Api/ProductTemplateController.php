<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Catalog\DeleteProductTemplateAction;
use App\Actions\Catalog\StoreProductTemplateAction;
use App\Actions\Catalog\UpdateProductTemplateAction;
use App\Http\Requests\Catalog\StoreProductTemplateRequest;
use App\Http\Requests\Catalog\UpdateProductTemplateRequest;
use App\Http\Resources\ProductTemplateResource;
use App\Models\ProductTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductTemplateController extends Controller
{
    public function __construct(
        private readonly StoreProductTemplateAction $store,
        private readonly UpdateProductTemplateAction $update,
        private readonly DeleteProductTemplateAction $delete,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = ProductTemplate::query()->with(['printerProvider']);

        if ($printerId = $request->string('printer_provider_id')->value()) {
            $query->where('printer_provider_id', $printerId);
        }
        if ($type = $request->string('type')->value()) {
            $query->where('type', $type);
        }

        return response()->json($this->paginated($query));
    }

    public function show(ProductTemplate $template): JsonResponse
    {
        return response()->json([
            'data' => new ProductTemplateResource(
                $template->load(['printerProvider', 'variants'])->loadCount(['variants', 'designProductMappings']),
            ),
        ]);
    }

    public function store(StoreProductTemplateRequest $request): JsonResponse
    {
        $template = $this->store->execute($request->user(), $request->validated());

        return response()->json(['data' => new ProductTemplateResource($template)], 201);
    }

    public function updateMe(UpdateProductTemplateRequest $request, ProductTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);
        $updated = $this->update->execute($template, $request->validated());

        return response()->json(['data' => new ProductTemplateResource($updated)]);
    }

    public function destroyMe(ProductTemplate $template): JsonResponse
    {
        $this->authorize('delete', $template);
        $this->delete->execute($template);

        return response()->json(null, 204);
    }
}
