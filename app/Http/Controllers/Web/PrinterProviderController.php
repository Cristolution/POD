<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\PrinterProviderProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PrinterProviderController extends Controller
{
    // ------------------------------------------------------------------
    // Public profile
    // ------------------------------------------------------------------

    public function show(Request $request, PrinterProviderProfile $printer): View
    {
        $printer->load(['user']);
        $printer->loadCount('productTemplates');

        $templates = $printer->productTemplates()
            ->with('variants')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        return view('pages.printers.show', [
            'printer' => $printer,
            'templates' => $templates,
        ]);
    }

    // ------------------------------------------------------------------
    // Printer self-service (role:printer_provider + auth middleware on the route)
    // ------------------------------------------------------------------

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $profile = $user->printerProviderProfile;

        abort_if($profile === null, 404, 'Printer profile not found.');

        $profile->loadCount(['productTemplates', 'pendingOrderItems']);

        $templates = $profile->productTemplates()
            ->withCount(['variants', 'designProductMappings'])
            ->orderByDesc('created_at')
            ->get();

        $orderItems = $profile->orderItems()
            ->with(['order', 'productVariant'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('pages.printer.dashboard', [
            'profile' => $profile,
            'templates' => $templates,
            'orderItems' => $orderItems,
        ]);
    }

    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $user->printerProviderProfile;

        abort_if($profile === null, 404, 'Printer profile not found.');

        return view('pages.printer.edit', [
            'profile' => $profile,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = $request->user()->printerProviderProfile;
        abort_if($profile === null, 404, 'Printer profile not found.');

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
        ]);

        $profile->update($data);

        return redirect()->route('printer.edit')->with('status', __('flash_profile_updated'));
    }

    // ------------------------------------------------------------------
    // Fulfilment queue — printer updates per-line-item status
    // ------------------------------------------------------------------

    /** Item status workflow: pending → received → printing → printed → handed_off → cancelled. */
    private const ITEM_STATUS_TRANSITIONS = [
        'received' => 'printing',
        'printing' => 'printed',
        'printed' => 'handed_off',
    ];

    public function fulfilment(Request $request): View
    {
        $user = $request->user();
        $profile = $user->printerProviderProfile;

        abort_if($profile === null, 404, 'Printer profile not found.');

        $items = $profile->orderItems()
            ->with([
                'order.customer',
                'designProductMapping.design',
                'productVariant',
            ])
            ->orderByRaw("FIELD(status, 'received', 'printing', 'printed', 'handed_off', 'pending', 'cancelled')")
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('pages.printer.fulfilment', [
            'profile' => $profile,
            'items' => $items,
            'transitions' => self::ITEM_STATUS_TRANSITIONS,
        ]);
    }

    public function advanceItem(Request $request, OrderItem $item): RedirectResponse
    {
        $profile = $request->user()->printerProviderProfile;

        abort_if($profile === null, 404, 'Printer profile not found.');

        // Ownership check: only the printer assigned to this item may update it.
        abort_unless($item->printer_provider_id === $profile->id, 403, 'Not your item to fulfil.');

        $allowed = self::ITEM_STATUS_TRANSITIONS[$item->status] ?? null;
        abort_if($allowed === null, 409, "Cannot advance from status '{$item->status}'.");

        $data = $request->validate([
            'next' => ['required', 'string', "in:{$allowed}"],
        ]);

        DB::transaction(function () use ($item, $data): void {
            $item->update(['status' => $data['next']]);
        });

        return redirect()->route('printer.fulfilment')
            ->with('status', __('flash_fulfilment_advanced', ['status' => $data['next']]));
    }
}
