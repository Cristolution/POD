<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Cart\ClearCartAction;
use App\Actions\Cart\DeleteCartItemAction;
use App\Actions\Cart\UpdateCartItemQuantityAction;
use App\Actions\Cart\UpsertCartItemAction;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Services\CartPricingService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(
        private readonly UpsertCartItemAction $upsert,
        private readonly UpdateCartItemQuantityAction $updateQuantity,
        private readonly DeleteCartItemAction $delete,
        private readonly ClearCartAction $clear,
        private readonly CartPricingService $pricing,
    ) {}

    public function index(): JsonResponse
    {
        $user = auth()->user();
        $items = $user->cartItems()
            ->with([
                'designProductMapping.design',
                'designProductMapping.productTemplate.printerProvider',
                'productVariant',
            ])
            ->get();

        return response()->json([
            'data' => CartItemResource::collection($items),
            'grand_total' => $this->pricing->grandTotal($items),
        ]);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $cartItem = $this->upsert->execute(
            user: $request->user(),
            mappingId: $request->string('design_product_mapping_id')->value(),
            variantId: $request->filled('product_variant_id') ? $request->string('product_variant_id')->value() : null,
            quantity: $request->integer('quantity'),
        );

        return response()->json(['data' => new CartItemResource($cartItem)], 201);
    }

    public function update(UpdateCartItemRequest $request, CartItem $item): JsonResponse
    {
        $updated = $this->updateQuantity->execute($item, $request->integer('quantity'));

        return response()->json(['data' => new CartItemResource($updated)]);
    }

    public function destroy(CartItem $item): JsonResponse
    {
        abort_unless($item->user_id === auth()->id(), 403);

        $this->delete->execute($item);

        return response()->json(null, 204);
    }

    public function clear(): JsonResponse
    {
        $this->clear->execute(auth()->user());

        return response()->json(null, 204);
    }
}
