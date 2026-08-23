<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Catalog\DeleteTagAction;
use App\Actions\Catalog\StoreTagAction;
use App\Actions\Catalog\UpdateTagAction;
use App\Http\Requests\Catalog\StoreTagRequest;
use App\Http\Requests\Catalog\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(
        private readonly StoreTagAction $store,
        private readonly UpdateTagAction $update,
        private readonly DeleteTagAction $delete,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Tag::query()->orderBy('name');

        if ($search = $request->string('search')->value()) {
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json($this->paginated($query));
    }

    public function show(Tag $tag): JsonResponse
    {
        return response()->json([
            'data' => new TagResource($tag->loadCount('designs')),
        ]);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $this->authorize('create', Tag::class);
        $tag = $this->store->execute($request->validated());

        return response()->json(['data' => new TagResource($tag)], 201);
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $this->authorize('update', $tag);
        $updated = $this->update->execute($tag, $request->validated());

        return response()->json(['data' => new TagResource($updated)]);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);
        $this->delete->execute($tag);

        return response()->json(null, 204);
    }
}
