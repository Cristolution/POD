<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WishlistController extends Controller
{
    /**
     * Add a design to the current user's wishlist. Idempotent.
     */
    public function store(Request $request, Design $design): RedirectResponse|JsonResponse
    {
        $wishlist = $request->user()->getOrCreateWishlist();

        DB::transaction(function () use ($wishlist, $design): void {
            // Use insertOrIgnore so duplicate adds don't throw — keeps the
            // wishlist idempotent and safe against double-clicks.
            DB::table('wishlist_items')->insertOrIgnore([
                'wishlist_id' => $wishlist->id,
                'design_id' => $design->id,
                'sort_order' => (int) $wishlist->items()->count(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json(['favourited' => true]);
        }

        return back()->with('status', 'Saved to wishlist.');
    }

    /**
     * Remove a design from the current user's wishlist. Idempotent.
     */
    public function destroy(Request $request, Design $design): RedirectResponse|JsonResponse
    {
        $wishlist = $request->user()->wishlist;
        if ($wishlist !== null) {
            WishlistItem::query()
                ->where('wishlist_id', $wishlist->id)
                ->where('design_id', $design->id)
                ->delete();
        }

        if ($request->wantsJson()) {
            return response()->json(['favourited' => false]);
        }

        return back()->with('status', 'Removed from wishlist.');
    }
}
