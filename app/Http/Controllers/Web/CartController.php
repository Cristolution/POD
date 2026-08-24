<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Cart\ClearCartAction;
use App\Actions\Cart\DeleteCartItemAction;
use App\Actions\Cart\UpdateCartItemQuantityAction;
use App\Actions\Cart\UpsertCartItemAction;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Services\CartPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private readonly UpsertCartItemAction $upsert,
        private readonly UpdateCartItemQuantityAction $updateQty,
        private readonly DeleteCartItemAction $delete,
        private readonly ClearCartAction $clear,
        private readonly CartPricingService $pricing,
    ) {}

    public function show(Request $request): View
    {
        $items = $request->user()
            ->cartItems()
            ->with(['designProductMapping.design.media', 'designProductMapping.productTemplate', 'productVariant'])
            ->get();

        return view('pages.cart.show', [
            'items' => $items,
            'grandTotal' => $this->pricing->grandTotal($items),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'design_product_mapping_id' => ['required', 'string', 'exists:design_product_mappings,id'],
            'product_variant_id' => ['nullable', 'string', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $this->upsert->execute(
            user: $request->user(),
            mappingId: $data['design_product_mapping_id'],
            variantId: $data['product_variant_id'] ?? null,
            quantity: $data['quantity'],
        );

        return redirect()->route('cart.show')->with('status', 'Added to cart.');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $this->updateQty->execute($item, $data['quantity']);

        return redirect()->route('cart.show');
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $this->delete->execute($item);

        return redirect()->route('cart.show')->with('status', 'Item removed.');
    }

    public function clearAll(Request $request): RedirectResponse
    {
        $this->clear->execute($request->user());

        return redirect()->route('cart.show')->with('status', 'Cart cleared.');
    }
}
