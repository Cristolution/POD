<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PrinterProviderProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return redirect()->route('printer.edit')->with('status', 'Profile updated.');
    }
}
