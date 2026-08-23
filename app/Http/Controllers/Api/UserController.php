<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Admin\DeleteUserAction;
use App\Actions\Admin\RestoreUserAction;
use App\Actions\Admin\UpdateUserAction;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UpdateUserAction $update,
        private readonly DeleteUserAction $delete,
        private readonly RestoreUserAction $restore,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with(['designerProfile', 'printerProviderProfile']);
        if ($role = $request->string('role')->value()) {
            $query->where('role', $role);
        }
        if ($search = $request->string('search')->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($this->paginated($query));
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json(['data' => new UserResource($user->load(['designerProfile', 'printerProviderProfile']))]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $updated = $this->update->execute($user, $request->validated());

        return response()->json(['data' => new UserResource($updated)]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $this->delete->execute($user);

        return response()->json(null, 204);
    }

    public function restore(User $user): JsonResponse
    {
        $this->authorize('restore', $user);
        $restored = $this->restore->execute($user);

        return response()->json(['data' => new UserResource($restored)]);
    }
}
