<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Setting\StoreSettingAction;
use App\Actions\Setting\UpdateSettingAction;
use App\Http\Requests\Setting\StoreSettingRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(
        private readonly StoreSettingAction $store,
        private readonly UpdateSettingAction $update,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Setting::query()->orderBy('key');

        if ($key = $request->string('key')->value()) {
            $query->where('key', 'like', $key.'%');
        }

        return response()->json($this->paginated($query));
    }

    public function show(Setting $setting): JsonResponse
    {
        $this->authorize('view', $setting);

        return response()->json([
            'data' => new SettingResource($setting),
        ]);
    }

    public function store(StoreSettingRequest $request): JsonResponse
    {
        $setting = $this->store->execute($request->validated());

        return response()->json(
            ['data' => new SettingResource($setting->refresh())],
            201,
        );
    }

    public function update(UpdateSettingRequest $request, Setting $setting): JsonResponse
    {
        $updated = $this->update->execute($setting, $request->validated());

        return response()->json([
            'data' => new SettingResource($updated),
        ]);
    }
}
