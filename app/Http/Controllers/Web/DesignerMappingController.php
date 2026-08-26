<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Catalog\DeleteDesignProductMappingAction;
use App\Actions\Catalog\StoreDesignProductMappingAction;
use App\Actions\Catalog\UpdateDesignProductMappingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreDesignerMappingRequest;
use App\Http\Requests\Web\UpdateDesignerMappingRequest;
use App\Models\DesignProductMapping;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DesignerMappingController extends Controller
{
    /**
     * Paginated list of design_product_mappings whose design belongs to this designer.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->designerProfile;
        abort_if($profile === null, 404, 'Designer profile not found.');

        $mappings = DesignProductMapping::query()
            ->with(['design', 'productTemplate', 'preferredPrinter.user'])
            ->whereHas('design', fn ($q) => $q->where('designer_id', $profile->id))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('pages.designer.mappings', ['mappings' => $mappings]);
    }

    /**
     * Designer-side create form. Lists only the designer's own designs.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', DesignProductMapping::class);

        $profile = $request->user()->designerProfile;
        abort_if($profile === null, 404, 'Designer profile not found.');

        return view('pages.designer.mappings.create', [
            'designs' => $profile->designs()->orderBy('title')->get(['id', 'title']),
            'templates' => ProductTemplate::query()->orderBy('name')->get(['id', 'name', 'type', 'base_cost']),
            'printers' => PrinterProviderProfile::query()
                ->with('user')
                ->orderBy('company_name')
                ->get(),
            'preselectedDesignId' => $request->query('design_id'),
        ]);
    }

    /**
     * Designer-side mapping creation. Ownership is re-checked by the action.
     */
    public function store(
        StoreDesignerMappingRequest $request,
        StoreDesignProductMappingAction $store,
    ): RedirectResponse {
        Gate::authorize('create', DesignProductMapping::class);

        $store->execute($request->user(), $request->validated());

        return redirect()
            ->route('designer.mappings')
            ->with('status', 'Mapping created.');
    }

    /**
     * Designer-side edit form. Only printer + price are editable.
     */
    public function edit(Request $request, DesignProductMapping $mapping): View
    {
        Gate::authorize('update', $mapping);

        $mapping->load(['design', 'productTemplate', 'preferredPrinter.user']);

        return view('pages.designer.mappings.edit', [
            'mapping' => $mapping,
            'printers' => PrinterProviderProfile::query()
                ->with('user')
                ->orderBy('company_name')
                ->get(),
        ]);
    }

    public function update(
        UpdateDesignerMappingRequest $request,
        DesignProductMapping $mapping,
        UpdateDesignProductMappingAction $update,
    ): RedirectResponse {
        Gate::authorize('update', $mapping);

        $update->execute($mapping, $request->validated());

        return redirect()
            ->route('designer.mappings')
            ->with('status', 'Mapping updated.');
    }

    /**
     * Designer-side delete. If the mapping has order items the action aborts
     * 409; we translate that into a friendly flash instead of a stack trace.
     */
    public function destroy(
        Request $request,
        DesignProductMapping $mapping,
        DeleteDesignProductMappingAction $delete,
    ): RedirectResponse {
        Gate::authorize('delete', $mapping);

        try {
            $delete->execute($mapping);
        } catch (HttpException $e) {
            return back()->withErrors(['mapping' => $e->getMessage()]);
        }

        return redirect()
            ->route('designer.mappings')
            ->with('status', 'Mapping deleted.');
    }
}
