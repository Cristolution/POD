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

        $design->load([
            'designer.user',
            'category',
            'tags',
            'media.productTemplate',
            'approvedReviews.customer' => fn ($q) => $q->select(['id', 'name']),
        ]);

        $mappings = $design->mappings()
            ->with(['productTemplate.activeVariants'])
            ->get();

        // Index of "design on product" mockups keyed by product_template_id so
        // each product card can look up its dedicated preview in O(1).
        $productMockups = $design->media
            ->where('collection_name', 'mockup')
            ->whereNotNull('product_template_id')
            ->keyBy('product_template_id');

        // Default mockup — used in the hero area and as fallback when a
        // product card has no per-product override.
        $defaultMockup = $design->media
            ->where('collection_name', 'mockup')
            ->whereNull('product_template_id')
            ->first();

        return view('pages.design.show', [
            'design' => $design,
            'mappings' => $mappings,
            'productMockups' => $productMockups,
            'defaultMockup' => $defaultMockup,
        ]);
    }
}
