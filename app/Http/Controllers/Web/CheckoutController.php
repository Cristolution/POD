<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Order\PlaceOrderFromCartAction;
use App\Http\Controllers\Controller;
use App\Models\DeliveryCompany;
use App\Models\Payment;
use App\Services\CartPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Valid `payments.method` DB ENUM values. Kept inline (not an Enum class)
     * to match the existing string-based status/method schema in Phase 2.
     */
    private const PAYMENT_METHODS = ['cash_on_delivery', 'bank_transfer', 'card'];

    public function __construct(
        private readonly CartPricingService $pricing,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $items = $user->cartItems()
            ->with(['designProductMapping.design.media', 'designProductMapping.productTemplate', 'productVariant'])
            ->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.show')->with('status', 'Your cart is empty.');
        }

        return view('pages.checkout.show', [
            'items' => $items,
            'grandTotal' => $this->pricing->grandTotal($items),
            'addresses' => $user->addresses()->orderBy('id')->get(),
            'deliveryCompanies' => DeliveryCompany::query()->orderBy('name')->get(),
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }

    public function place(Request $request, PlaceOrderFromCartAction $place): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'address_id' => [
                'required',
                'integer',
                Rule::exists('addresses', 'id')->where('user_id', $user->id),
            ],
            'payment_method' => ['required', 'string', Rule::in(self::PAYMENT_METHODS)],
        ]);

        $order = $place->execute(
            user: $user,
            shippingAddressId: (int) $data['address_id'],
        );

        // PlaceOrderFromCartAction does not create a Payment record — the
        // customer-selected method is recorded here so admin fulfilment can
        // confirm/reject it later (Payment is a HasMany on Order, not BelongsTo).
        Payment::create([
            'order_id' => $order->id,
            'method' => $data['payment_method'],
            'status' => 'pending',
        ]);

        return redirect()->route('orders.confirmation', $order)->with('status', 'Order placed successfully.');
    }
}
