<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Order\CancelOrderAction;
use App\Actions\Order\DeleteOrderAction;
use App\Actions\Order\PlaceOrderFromCartAction;
use App\Actions\Order\RestoreOrderAction;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly PlaceOrderFromCartAction $place,
        private readonly CancelOrderAction $cancel,
        private readonly DeleteOrderAction $delete,
        private readonly RestoreOrderAction $restore,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()->with([
            'customer',
            'shippingAddress',
            'items',
            'payments',
            'shipments',
        ]);

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        return response()->json($this->paginated($query->orderByDesc('created_at')));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $order = $this->place->execute(
            user: $request->user(),
            shippingAddressId: $request->integer('shipping_address_id'),
        );

        return response()->json(['data' => new OrderResource($order)], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'data' => new OrderResource(
                $order->load(['items.designProductMapping', 'items.productVariant', 'items.printerProvider', 'payments', 'shipments']),
            ),
        ]);
    }

    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        $cancelled = $this->cancel->execute($order);

        return response()->json(['data' => new OrderResource($cancelled)]);
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete', $order);

        $this->delete->execute($order);

        return response()->json(null, 204);
    }

    public function restore(Order $order): JsonResponse
    {
        $this->authorize('restore', $order);

        $restored = $this->restore->execute($order);

        return response()->json(['data' => new OrderResource($restored)]);
    }
}
