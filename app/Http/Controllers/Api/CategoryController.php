<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Catalog\DeleteCategoryAction;
use App\Actions\Catalog\StoreCategoryAction;
use App\Actions\Catalog\UpdateCategoryAction;
use App\Http\Requests\Catalog\StoreCategoryRequest;
use App\Http\Requests\Catalog\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private readonly StoreCategoryAction $store,
        private readonly UpdateCategoryAction $update,
        private readonly DeleteCategoryAction $delete,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query();
        if ($parentId = $request->string('parent_id')->value()) {
            $query->where('parent_id', $parentId);
        }

        return response()->json($this->paginated($query));
    }

    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return response()->json([
            'data' => new CategoryResource(
                $category->load(['parent', 'children'])->loadCount('designs')
            ),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);
        $category = $this->store->execute($request->validated());

        return response()->json(['data' => new CategoryResource($category)], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);
        $updated = $this->update->execute($category, $request->validated());

        return response()->json(['data' => new CategoryResource($updated)]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);
        $this->delete->execute($category);

        return response()->json(null, 204);
    }
}
