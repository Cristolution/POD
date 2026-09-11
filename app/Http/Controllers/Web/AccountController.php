<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Role-aware dashboard: the header's user-name link lands the user on
     * /account, which renders the right view per role. Designers and printer
     * providers see their own dashboards so they can navigate back from any
     * public page (browse, design detail, etc.) without typing a URL.
     */
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        if ($user->isDesigner()) {
            return $this->renderDesignerDashboard($user);
        }

        if ($user->isPrinterProvider()) {
            return $this->renderPrinterDashboard($user);
        }

        return $this->renderCustomerDashboard($user);
    }

    private function renderDesignerDashboard(User $user): View
    {
        $profile = $user->designerProfile;
        abort_if($profile === null, 404, 'Designer profile not found.');

        $profile->loadCount(['designs', 'publishedDesigns']);

        $designs = $profile->designs()
            ->with(['category', 'media'])
            ->withCount('mappings')
            ->orderByDesc('created_at')
            ->get();

        return view('pages.designer.dashboard', [
            'profile' => $profile,
            'designs' => $designs,
        ]);
    }

    private function renderPrinterDashboard(User $user): View
    {
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

    private function renderCustomerDashboard(User $user): View
    {
        $user->loadCount(['addresses', 'orders']);

        return view('pages.account.dashboard', [
            'user' => $user,
            'recentOrders' => $user->orders()
                ->with(['shippingAddress', 'payments'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $user->update($data);

        return redirect()->route('account.dashboard')->with('status', __('flash_profile_updated'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => __('flash_current_password_incorrect')]);
        }

        $user->update(['password' => $data['password']]); // hashed via cast

        return redirect()->route('account.dashboard')->with('status', __('flash_password_updated'));
    }

    public function wishlist(Request $request): View
    {
        $user = $request->user();
        $wishlist = $user->wishlist()->firstOrCreate(['user_id' => $user->id]);

        $items = $wishlist->designs()
            ->with(['media', 'designer.user', 'mappings.productTemplate'])
            ->get();

        return view('pages.account.wishlist', [
            'wishlist' => $wishlist,
            'items' => $items,
        ]);
    }
}
