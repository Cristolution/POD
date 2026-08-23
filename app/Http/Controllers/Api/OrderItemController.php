<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Order\ReassignPrinterAction;
use App\Actions\Order\UpdateOrderItemStatusAction;
use App\Http\Requests\Order\ReassignPrinterRequest;
use App\Http\Requests\Order\UpdateStatusRequest;
use App\Http\Resources\OrderItemResource;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;

class OrderItemController extends Controller
{
    public function __construct(
        private readonly UpdateOrderItemStatusAction $updateStatus,
        private readonly ReassignPrinterAction $reassignPrinter,
    ) {}

    public function show(OrderItem $item): JsonResponse
    {
        $this->authorize('view', $item);

        return response()->json([
            'data' => new OrderItemResource(
                $item->load(['order', 'designProductMapping', 'productVariant', 'printerProvider']),
            ),
        ]);
    }

    public function updateStatus(UpdateStatusRequest $request, OrderItem $item): JsonResponse
    {
        $this->authorize('update', $item);

        $updated = $this->updateStatus->execute(
            item: $item,
            newStatus: $request->string('status')->value(),
        );

        return response()->json(['data' => new OrderItemResource($updated)]);
    }

    public function reassignPrinter(ReassignPrinterRequest $request, Order $order, OrderItem $item): JsonResponse
    {
        $this->authorize('reassign', $item);

        $reassigned = $this->reassignPrinter->execute(
            item: $item,
            newPrinterProviderId: $request->string('printer_provider_id')->value(),
        );

        return response()->json(['data' => new OrderItemResource($reassigned)]);
    }
}
