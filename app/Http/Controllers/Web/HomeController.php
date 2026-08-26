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
            'categories' => Category::query()
                ->withCount(['designs' => fn ($q) => $q->where('status', 'published')->whereNull('deleted_at')])
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(),

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
