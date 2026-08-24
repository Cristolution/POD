<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Notification\BroadcastNotificationAction;
use App\Actions\Notification\MarkAllReadAction;
use App\Http\Requests\Notification\StoreNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly MarkAllReadAction $markAllRead,
        private readonly BroadcastNotificationAction $broadcast,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->notifications()->orderByDesc('created_at');

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        if ($type = $request->string('type')->value()) {
            $query->where('type', $type);
        }

        $page = (int) $request->integer('page', 1);
        $perPage = 25;

        return response()->json($query->paginate($perPage, ['*'], 'page', $page));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        $this->authorize('markRead', $notification);

        $notification->markAsRead();

        return response()->json([
            'data' => new NotificationResource($notification->refresh()),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->markAllRead->execute($request->user());

        return response()->json(['marked' => $count]);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json(null, 204);
    }

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $sent = $this->broadcast->execute(
            adminActor: $request->user(),
            type: $request->string('type')->value(),
            message: $request->string('message')->value(),
            targetUserIds: $request->input('target_user_ids', []),
        );

        return response()->json(['sent' => $sent], 201);
    }
}
