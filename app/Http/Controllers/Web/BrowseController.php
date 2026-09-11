<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Design;
use App\Models\DesignerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrowseController extends Controller
{
    private const PER_PAGE = 24;

    private const SORT_OPTIONS = [
        'newest' => 'Newest',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
    ];

    public function designs(Request $request): View
    {
        $query = Design::query()
            ->with([
                'designer.user',
                'category',
                'category.parent',
                'media',
                'tags',
                'mappings.productTemplate',
            ])
            ->withCount('mappings')
            ->withMin('mappings as min_price', 'final_price')
            ->withMax('mappings as max_price', 'final_price')
            ->where('status', 'published')
            ->whereNull('deleted_at');

        $categoryId = $request->integer('category') ?: null;
        if ($categoryId) {
            // Include the chosen category and any descendants so a root filter
            // surfaces all of its sub-categories' designs in one click.
            $childIds = Category::where('parent_id', $categoryId)->pluck('id')->all();
            $descendantIds = Category::whereIn('id', $childIds)->pluck('id')->all();
            $descendantIds[] = $categoryId;
            $query->whereIn('category_id', array_values(array_unique($descendantIds)));
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

        $designerIds = array_filter((array) $request->input('designer', []));
        if ($designerIds) {
            $query->whereHas('designer', fn (Builder $q) => $q->whereIn('id', $designerIds));
        }

        $priceMin = $request->float('price_min');
        $priceMax = $request->float('price_max');
        if ($priceMin > 0 || $priceMax > 0) {
            $query->whereHas('mappings', function (Builder $q) use ($priceMin, $priceMax) {
                if ($priceMin > 0) {
                    $q->where('final_price', '>=', $priceMin);
                }
                if ($priceMax > 0) {
                    $q->where('final_price', '<=', $priceMax);
                }
            });
        }

        $sort = $request->string('sort')->value();
        if (! array_key_exists($sort, self::SORT_OPTIONS)) {
            $sort = 'newest';
        }
        match ($sort) {
            'price_asc' => $query->orderBy('min_price', 'asc'),
            'price_desc' => $query->orderBy('min_price', 'desc'),
            default => $query->orderByDesc('created_at'),
        };

        $designs = $query->paginate(self::PER_PAGE)->withQueryString();

        return view('pages.browse.designs', [
            'designs' => $designs,
            'categories' => Category::with('children')
                ->roots()
                ->withCount(['designs' => fn ($q) => $q->where('status', 'published')])
                ->orderBy('name')
                ->get(),
            'designers' => DesignerProfile::query()
                ->with(['user'])
                ->withCount('publishedDesigns')
                ->whereHas('publishedDesigns')
                ->orderByDesc('published_designs_count')
                ->orderBy('id')
                ->limit(24)
                ->get(),
            'activeCategory' => $categoryId ? Category::find($categoryId) : null,
            'activeDesigners' => $designerIds
                ? DesignerProfile::with('user')->whereIn('id', $designerIds)->get()
                : collect(),
            'priceMin' => $priceMin ?: null,
            'priceMax' => $priceMax ?: null,
            'sort' => $sort,
            'sortOptions' => self::SORT_OPTIONS,
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
            ->with(['user'])
            ->withCount('publishedDesigns')
            ->orderByDesc('published_designs_count')
            ->paginate(24);

        return view('pages.browse.designers', compact('designers'));
    }
}
