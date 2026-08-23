<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Shipment\CreateShipmentAction;
use App\Actions\Shipment\DeleteShipmentAction;
use App\Actions\Shipment\MarkDeliveredAction;
use App\Actions\Shipment\MarkShippedAction;
use App\Actions\Shipment\UpdateTrackingAction;
use App\Http\Requests\Shipment\StoreShipmentRequest;
use App\Http\Requests\Shipment\UpdateTrackingRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(
        private readonly CreateShipmentAction $create,
        private readonly MarkShippedAction $markShipped,
        private readonly MarkDeliveredAction $markDelivered,
        private readonly UpdateTrackingAction $updateTracking,
        private readonly DeleteShipmentAction $delete,
    ) {}

    public function index(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Customer: scope to own orders (defensive — orders are also policy-protected).
        if ($user !== null && $user->isCustomer() && $order->customer_id !== $user->id) {
            abort(403);
        }

        $query = Shipment::query()
            ->with(['order', 'printerProvider', 'deliveryCompany'])
            ->where('order_id', $order->id);

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        // Printer: further scope to their own shipments.
        if ($user !== null && $user->isPrinterProvider()) {
            $printerProfileId = $user->printerProviderProfile?->id;
            if ($printerProfileId === null) {
                abort(403);
            }
            $query->where('printer_provider_id', $printerProfileId);
        }

        return response()->json($this->paginated($query->orderByDesc('created_at')));
    }

    public function show(Shipment $shipment): JsonResponse
    {
        $this->authorize('view', $shipment);

        return response()->json([
            'data' => new ShipmentResource(
                $shipment->load(['order', 'printerProvider', 'deliveryCompany']),
            ),
        ]);
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $this->authorize('create', Shipment::class);

        $shipment = $this->create->execute($request->validated());

        return response()->json(
            ['data' => new ShipmentResource($shipment->load(['order', 'printerProvider', 'deliveryCompany']))],
            201,
        );
    }

    public function markShipped(Shipment $shipment): JsonResponse
    {
        $this->authorize('markShipped', $shipment);

        $updated = $this->markShipped->execute($shipment);

        return response()->json([
            'data' => new ShipmentResource($updated->load(['order', 'printerProvider', 'deliveryCompany'])),
        ]);
    }

    public function markDelivered(Shipment $shipment): JsonResponse
    {
        $this->authorize('markDelivered', $shipment);

        $updated = $this->markDelivered->execute($shipment);

        return response()->json([
            'data' => new ShipmentResource($updated->load(['order', 'printerProvider', 'deliveryCompany'])),
        ]);
    }

    public function updateTracking(UpdateTrackingRequest $request, Shipment $shipment): JsonResponse
    {
        $this->authorize('update', $shipment);

        $updated = $this->updateTracking->execute(
            shipment: $shipment,
            trackingNumber: $request->string('tracking_number')->value(),
        );

        return response()->json([
            'data' => new ShipmentResource($updated->load(['order', 'printerProvider', 'deliveryCompany'])),
        ]);
    }

    public function destroy(Shipment $shipment): JsonResponse
    {
        $this->authorize('delete', $shipment);

        $this->delete->execute($shipment);

        return response()->json(null, 204);
    }
}
