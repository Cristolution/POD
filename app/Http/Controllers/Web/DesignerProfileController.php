<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DesignerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignerProfileController extends Controller
{
    // ------------------------------------------------------------------
    // Public profile
    // ------------------------------------------------------------------

    public function show(Request $request, DesignerProfile $designer): View
    {
        $designer->load(['user']);
        $designer->loadCount(['designs', 'publishedDesigns']);

        $designs = $designer->publishedDesigns()
            ->with(['designer.user', 'category', 'media'])
            ->withCount('mappings')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('pages.designers.show', [
            'designer' => $designer,
            'designs' => $designs,
        ]);
    }

    // ------------------------------------------------------------------
    // Designer self-service (role:designer + auth middleware on the route)
    // ------------------------------------------------------------------

    public function dashboard(Request $request): View
    {
        $user = $request->user();
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

    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $user->designerProfile;

        abort_if($profile === null, 404, 'Designer profile not found.');

        return view('pages.designer.edit', [
            'profile' => $profile,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'designer' && $user->designerProfile !== null, 403);

        $data = $request->validate([
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);

        $user->designerProfile->update($data);

        return redirect()->route('designer.edit')->with('status', 'Profile updated.');
    }
}
