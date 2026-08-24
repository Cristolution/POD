<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('pages.account.addresses.index', [
            'addresses' => $request->user()->addresses()->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('pages.account.addresses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'line1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $request->user()->addresses()->create($data);

        return redirect()->route('account.addresses.index')->with('status', 'Address added.');
    }

    public function edit(Request $request, Address $address): View
    {
        abort_unless($address->user_id === $request->user()->id, 403);

        return view('pages.account.addresses.edit', ['address' => $address]);
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'line1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $address->update($data);

        return redirect()->route('account.addresses.index')->with('status', 'Address updated.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);

        // Refuse deletion if this address is referenced by an existing order.
        if (Order::where('shipping_address_id', $address->id)->exists()) {
            return back()->withErrors(['address' => 'Cannot delete: this address is used by an existing order.']);
        }

        $address->delete();

        return redirect()->route('account.addresses.index')->with('status', 'Address removed.');
    }
}
