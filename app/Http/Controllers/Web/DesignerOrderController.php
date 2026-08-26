<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignerOrderController extends Controller
{
    /**
     * Paginated list of order items that involve this designer's designs.
     *
     * The join chain is order_items.design_product_mapping_id → mappings.design_id → designs.designer_id.
     * Filters via whereHas to keep the SQL clean and let Eloquent optimise the
     * subquery.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->designerProfile;
        abort_if($profile === null, 404, 'Designer profile not found.');

        $items = OrderItem::query()
            ->with([
                'order',
                'designProductMapping.design',
                'productVariant',
                'printerProvider.user',
            ])
            ->whereHas('designProductMapping.design', fn ($q) => $q->where('designer_id', $profile->id))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('pages.designer.orders', ['items' => $items]);
    }
}
