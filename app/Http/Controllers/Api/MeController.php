<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\User\RequestDeleteMeAction;
use App\Actions\User\UpdateMeAction;
use App\Actions\User\UpdatePasswordAction;
use App\Http\Requests\User\UpdateMeRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    public function __construct(
        private readonly UpdateMeAction $updateMe,
        private readonly UpdatePasswordAction $updatePassword,
        private readonly RequestDeleteMeAction $requestDeleteMe,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(
                auth()->user()->load(['designerProfile', 'printerProviderProfile']),
            ),
        ]);
    }

    public function update(UpdateMeRequest $request): JsonResponse
    {
        $user = $this->updateMe->execute(auth()->user(), $request->validated());

        return response()->json(['data' => new UserResource($user)]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->updatePassword->execute(
            auth()->user(),
            $request->string('current_password')->value(),
            $request->string('password')->value(),
        );

        return response()->json(null, 204);
    }

    public function destroy(): JsonResponse
    {
        $user = auth()->user();

        if ($user->trashed()) {
            return response()->json(null, 410);
        }

        $this->requestDeleteMe->execute($user);

        return response()->json(null, 202);
    }
}
