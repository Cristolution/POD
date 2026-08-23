<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Catalog\DeleteProductVariantAction;
use App\Actions\Catalog\StoreProductVariantAction;
use App\Actions\Catalog\UpdateProductVariantAction;
use App\Http\Requests\Catalog\StoreProductVariantRequest;
use App\Http\Requests\Catalog\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly StoreProductVariantAction $store,
        private readonly UpdateProductVariantAction $update,
        private readonly DeleteProductVariantAction $delete,
    ) {}

    public function index(Request $request, ProductTemplate $template): JsonResponse
    {
        $query = $template->variants();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->paginate(min($request->integer('per_page', 25), 100)));
    }

    public function store(StoreProductVariantRequest $request, ProductTemplate $template): JsonResponse
    {
        $stub = new ProductVariant(['product_template_id' => $template->id]);
        $stub->setRelation('productTemplate', $template);
        $this->authorize('create', $stub);

        $variant = $this->store->execute($template, $request->validated());

        return response()->json(['data' => new ProductVariantResource($variant)], 201);
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $variant): JsonResponse
    {
        $this->authorize('update', $variant);
        $updated = $this->update->execute($variant, $request->validated());

        return response()->json(['data' => new ProductVariantResource($updated)]);
    }

    public function destroy(ProductVariant $variant): JsonResponse
    {
        $this->authorize('delete', $variant);
        $this->delete->execute($variant);

        return response()->json(null, 204);
    }
}
