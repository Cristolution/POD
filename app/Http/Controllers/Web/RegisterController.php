<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /** Roles a visitor may self-select at /register. Admins and printer providers are admin-created only. */
    private const SELF_REGISTRATION_ROLES = ['customer', 'designer'];

    public function create(): View
    {
        return view('pages.auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            // Optional: defaults to 'customer' so the existing /register POST contract stays
            // backward-compatible with API + web tests that omit role.
            'role' => ['sometimes', 'string', Rule::in(self::SELF_REGISTRATION_ROLES)],
        ]);

        $role = $data['role'] ?? 'customer';

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $role,
        ]);

        // Eager-create the DesignerProfile so the new designer can land on /designer
        // immediately. The dashboard controller aborts 404 if the profile is missing,
        // so we can't rely on the lazy create inside StoreDesignAction::resolveProfile().
        if ($role === 'designer') {
            $user->designerProfile()->firstOrCreate([]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended($this->landingRouteFor($user))
            ->with('status', $role === 'designer'
                ? 'Welcome! Your designer account is pending admin review.'
                : 'Welcome!');
    }

    private function landingRouteFor(User $user): string
    {
        if ($user->isDesigner()) {
            return route('designer.dashboard');
        }
        if ($user->isPrinterProvider()) {
            return route('printer.dashboard');
        }

        return route('home');
    }
}
