<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RegisterAction;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterAction $register,
        private readonly LoginAction $login,
        private readonly LogoutAction $logout,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        [$user, $token] = $this->register->execute($request->validated());

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->login->execute(
            email: $request->string('email')->lower()->value(),
            password: $request->string('password')->value(),
            deviceName: $request->string('device_name')->value() ?: null,
        );

        abort_if($result === null, 401, 'Invalid credentials.');

        [$user, $token] = $result;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->logout->execute(auth()->user());

        return response()->json(null, 204);
    }
}
