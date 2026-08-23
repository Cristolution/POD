<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Designer\DeleteDesignerProfileAction;
use App\Actions\Designer\StoreDesignerProfileAction;
use App\Actions\Designer\UpdateDesignerProfileAction;
use App\Http\Requests\Designer\StoreDesignerProfileRequest;
use App\Http\Requests\Designer\UpdateDesignerProfileRequest;
use App\Http\Resources\DesignerProfileResource;
use App\Models\DesignerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignerController extends Controller
{
    public function __construct(
        private readonly StoreDesignerProfileAction $store,
        private readonly UpdateDesignerProfileAction $update,
        private readonly DeleteDesignerProfileAction $delete,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DesignerProfile::query()->with(['user', 'designs']);
        if ($userId = $request->string('user_id')->value()) {
            $query->where('user_id', $userId);
        }

        return response()->json($this->paginated($query));
    }

    public function show(DesignerProfile $designer): JsonResponse
    {
        return response()->json([
            'data' => new DesignerProfileResource($designer->load(['user', 'designs'])->loadCount('designs')),
        ]);
    }

    public function store(StoreDesignerProfileRequest $request): JsonResponse
    {
        $profile = $this->store->execute($request->user(), $request->validated());

        return response()->json(['data' => new DesignerProfileResource($profile)], 201);
    }

    public function updateMe(UpdateDesignerProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->designerProfile ?? abort(404, 'Designer profile not found.');
        $this->authorize('update', $profile);
        $updated = $this->update->execute($profile, $request->validated());

        return response()->json(['data' => new DesignerProfileResource($updated)]);
    }

    public function destroyMe(Request $request): JsonResponse
    {
        $profile = $request->user()->designerProfile ?? abort(404, 'Designer profile not found.');
        $this->authorize('delete', $profile);
        $this->delete->execute($profile);

        return response()->json(null, 204);
    }
}
