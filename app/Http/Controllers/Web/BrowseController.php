<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrowseController extends Controller
{
    public function designs(Request $request): View
    {
        $query = Design::query()
            ->with(['designer.user', 'category', 'media', 'tags'])
            ->withCount('mappings')
            ->where('status', 'published')
            ->whereNull('deleted_at');

        if ($categoryId = $request->integer('category')) {
            $query->where('category_id', $categoryId);
        }
        // No tag slug column exists; match on name.
        if ($tagName = $request->string('tag')->value()) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $tagName));
        }
        if ($search = $request->string('q')->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $designs = $query->orderByDesc('created_at')->paginate(24)->withQueryString();

        return view('pages.browse.designs', [
            'designs' => $designs,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function categories(Request $request): View
    {
        $categories = Category::withCount(['designs' => fn ($q) => $q->where('status', 'published')])
            ->with(['children'])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('pages.browse.categories', compact('categories'));
    }

    public function designers(Request $request): View
    {
        $designers = DesignerProfile::query()
            ->with(['user', 'publishedDesigns'])
            ->withCount('publishedDesigns')
            ->orderByDesc('published_designs_count')
            ->paginate(24);

        return view('pages.browse.designers', compact('designers'));
    }
}
