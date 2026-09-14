<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('pages.home', [
            // Six most-recent published designs drive the hero strip. Capped
            // server-side so the homepage stays light even on a mature store.
            'featuredDesigns' => Design::query()
                ->with(['designer.user', 'category', 'media'])
                ->withCount('mappings')
                ->where('status', 'published')
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),

            // Root categories only — child categories are reachable from the
            // browse/categories page. Counts use the same `published` filter
            // the browse page does, so the homepage never advertises an
            // empty category.
            //
            // The count is recursive — it counts every published design in
            // this category AND its descendants — so the badge matches
            // what the user actually sees when they click through (the
            // browse page also expands parent filters to include
            // descendants). A naïve `designs_count` here would understate
            // every category whose children hold the actual designs.
            'categories' => Category::query()
                ->with('children.children.children.children') // deep eager-load for descendantIds()
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get()
                ->map(function (Category $category) {
                    $descendantIds = $category->descendantIds();
                    $count = Design::query()
                        ->whereIn('category_id', $descendantIds)
                        ->where('status', 'published')
                        ->whereNull('deleted_at')
                        ->count();

                    // Splat a synthetic attribute so the Blade view can
                    // read it without knowing about the lookup.
                    $category->setAttribute('total_designs_count', $count);

                    return $category;
                })
                ->filter(fn (Category $c) => $c->getAttribute('total_designs_count') > 0)
                ->values(),

            // Designers ranked by how much published work they actually have
            // — no point surfacing an empty portfolio on the homepage.
            // whereHas filters the rows; withCount drives the ORDER BY.
            'designers' => DesignerProfile::query()
                ->with(['user'])
                ->withCount(['publishedDesigns'])
                ->whereHas('publishedDesigns')
                ->orderByDesc('published_designs_count')
                ->limit(4)
                ->get(),
        ]);
    }
}
