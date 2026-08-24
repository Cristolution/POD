<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignDetailController extends Controller
{
    public function show(Request $request, Design $design): View
    {
        // Authorize: anonymous can only see published; designer (owner) can see own drafts;
        // admin can see everything.
        $user = $request->user();
        $isOwner = $user && $design->designer_id === $user->designerProfile?->id;
        $isAdmin = $user?->isAdmin() ?? false;

        abort_unless(
            $design->status === 'published' || $isOwner || $isAdmin,
            404
        );

        $design->load(['designer.user', 'category', 'tags', 'media']);

        $mappings = $design->mappings()
            ->with(['productTemplate.activeVariants'])
            ->get();

        return view('pages.design.show', [
            'design' => $design,
            'mappings' => $mappings,
        ]);
    }
}
