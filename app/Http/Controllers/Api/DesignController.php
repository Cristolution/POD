<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Catalog\DeleteDesignAction;
use App\Actions\Catalog\StoreDesignAction;
use App\Actions\Catalog\TransferDesignAction;
use App\Actions\Catalog\UpdateDesignAction;
use App\Http\Requests\Catalog\StoreDesignRequest;
use App\Http\Requests\Catalog\UpdateDesignRequest;
use App\Http\Resources\DesignResource;
use App\Models\Design;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    public function __construct(
        private readonly StoreDesignAction $store,
        private readonly UpdateDesignAction $update,
        private readonly DeleteDesignAction $delete,
        private readonly TransferDesignAction $transfer,
    ) {}

    /**
     * GET /api/designs
     *
     * Filters: designer_id, category_id, status, search.
     *
     * Public visibility: anonymous callers see only published designs. An
     * authenticated designer additionally sees their own non-published
     * designs; admins see everything (already covered by `before()`).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Design::query()
            ->with(['designer.user', 'category', 'tags']);

        $user = $request->user();

        if (! $user) {
            $query->where('status', 'published');
        } elseif (! $user->isAdmin()) {
            $ownProfileId = $user->designerProfile?->id;
            $query->where(function (Builder $q) use ($ownProfileId): void {
                $q->where('status', 'published');
                if ($ownProfileId) {
                    $q->orWhere('designer_id', $ownProfileId);
                }
            });
        }

        if ($designerId = $request->string('designer_id')->value()) {
            $query->where('designer_id', $designerId);
        }

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        } elseif (! $user) {
            // public default: already forced above, but reassert in case
            // other filters overrode it via the `where` builder state.
            $query->where('status', 'published');
        }

        if ($search = $request->string('search')->value()) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        return response()->json($this->paginated($query));
    }

    public function show(Request $request, Design $design): JsonResponse
    {
        $this->authorize('view', $design);

        return response()->json([
            'data' => new DesignResource(
                $design->load(['designer.user', 'category', 'tags']),
            ),
        ]);
    }

    public function store(StoreDesignRequest $request): JsonResponse
    {
        $this->authorize('create', Design::class);

        $design = $this->store->execute($request->user(), $request->validated());

        return response()->json(['data' => new DesignResource($design)], 201);
    }

    public function updateMe(UpdateDesignRequest $request, Design $design): JsonResponse
    {
        $this->authorize('update', $design);

        $updated = $this->update->execute($design, $request->validated());

        return response()->json(['data' => new DesignResource($updated)]);
    }

    public function destroyMe(Request $request, Design $design): JsonResponse
    {
        $this->authorize('delete', $design);

        $this->delete->execute($design);

        return response()->json(null, 204);
    }

    public function transfer(Request $request, Design $design): JsonResponse
    {
        $this->authorize('transfer', $design);

        $data = $request->validate([
            'designer_id' => ['required', 'string', 'exists:designer_profiles,id'],
        ]);

        $transferred = $this->transfer->execute($design, $data['designer_id']);

        return response()->json(['data' => new DesignResource($transferred)]);
    }
}
