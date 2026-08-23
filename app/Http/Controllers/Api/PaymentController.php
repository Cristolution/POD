<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Payment\ConfirmPaymentAction;
use App\Actions\Payment\CreatePaymentForOrderAction;
use App\Actions\Payment\DeletePaymentAction;
use App\Actions\Payment\RejectPaymentAction;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdateStatusRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CreatePaymentForOrderAction $create,
        private readonly ConfirmPaymentAction $confirm,
        private readonly RejectPaymentAction $reject,
        private readonly DeletePaymentAction $delete,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::query()
            ->with(['order', 'confirmedByAdmin'])
            ->orderByDesc('created_at');

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        return response()->json($this->paginated($query));
    }

    public function meIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Payment::query()
            ->with(['order', 'confirmedByAdmin'])
            ->whereHas('order', fn ($q) => $q->where('customer_id', $user->id))
            ->orderByDesc('created_at');

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        return response()->json($this->paginated($query));
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        return response()->json([
            'data' => new PaymentResource(
                $payment->load(['order', 'confirmedByAdmin']),
            ),
        ]);
    }

    public function store(StorePaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('create', [Payment::class, $order]);

        $payment = $this->create->execute(
            order: $order,
            method: $request->string('method')->value(),
        );

        return response()->json(
            ['data' => new PaymentResource($payment->load(['order', 'confirmedByAdmin']))],
            201,
        );
    }

    public function confirm(UpdateStatusRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        $confirmed = $this->confirm->execute(
            payment: $payment,
            admin: $request->user(),
        );

        return response()->json([
            'data' => new PaymentResource($confirmed->load(['order', 'confirmedByAdmin'])),
        ]);
    }

    public function reject(UpdateStatusRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        $rejected = $this->reject->execute(
            payment: $payment,
            admin: $request->user(),
        );

        return response()->json([
            'data' => new PaymentResource($rejected->load(['order', 'confirmedByAdmin'])),
        ]);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);

        $this->delete->execute($payment);

        return response()->json(null, 204);
    }
}
