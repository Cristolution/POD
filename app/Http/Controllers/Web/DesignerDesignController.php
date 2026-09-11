<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Catalog\DeleteDesignAction;
use App\Actions\Catalog\StoreDesignAction;
use App\Actions\Catalog\UpdateDesignAction;
use App\Actions\Media\UploadMediaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreDesignerDesignRequest;
use App\Http\Requests\Web\UpdateDesignerDesignRequest;
use App\Models\Category;
use App\Models\Design;
use App\Models\Media;
use App\Models\ProductTemplate;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Designer-facing design CRUD under /designer/designs/*.
 *
 * Reuses the framework-agnostic Catalog actions (StoreDesignAction,
 * UpdateDesignAction, DeleteDesignAction) and the media UploadMediaAction
 * exactly like the API DesignController does — but wired to Blade views
 * and HTML form posts instead of JSON.
 *
 * Ownership / authorization is delegated to DesignPolicy via Gate::authorize().
 * The form requests enforce the file MIME/size rules and strip the file fields
 * from the design data so the action receives clean catalog data only.
 */
class DesignerDesignController extends Controller
{
    public function create(Request $request): View
    {
        Gate::authorize('create', Design::class);

        return view('pages.designer.designs.create', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name']),
            'productTypes' => ProductTemplate::query()
                ->select('type')
                ->distinct()
                ->orderBy('type')
                ->pluck('type')
                ->all(),
        ]);
    }

    public function store(StoreDesignerDesignRequest $request, StoreDesignAction $store, UploadMediaAction $upload): RedirectResponse
    {
        Gate::authorize('create', Design::class);

        $data = $request->validated();
        $mockup = $request->file('mockup');
        $printFile = $request->file('print_file');
        $productMockups = $request->file('product_mockups') ?? [];
        unset($data['mockup'], $data['print_file'], $data['product_mockups']);

        $design = $store->execute($request->user(), $data);

        // Default mockup + print file — both required for any published design.
        $upload->execute($mockup, $design, 'mockup');
        $upload->execute($printFile, $design, 'print_file');

        // Optional per-product mockups — keyed by product_template_id.
        // Designer uploads only when they want a custom preview for that
        // specific product type (e.g. "this design on a t-shirt"). The
        // design page falls back to the default mockup when no override exists.
        foreach ($productMockups as $productTemplateId => $file) {
            $upload->execute($file, $design, 'mockup', (string) $productTemplateId);
        }

        return redirect()
            ->route('designer.designs.show', $design)
            ->with('status', __('flash_design_created'));
    }

    public function show(Request $request, Design $design): View
    {
        Gate::authorize('view', $design);

        $design->load([
            'category',
            'tags',
            'media',
            'mappings.productTemplate',
            'mappings.preferredPrinter.user',
            'mappings.orderItems' => fn ($q) => $q->latest('created_at')->limit(5),
            'mappings.orderItems.order',
        ]);

        return view('pages.designer.designs.show', [
            'design' => $design,
            'recentOrderItems' => $design->mappings->flatMap->orderItems->sortByDesc('created_at')->take(5)->values(),
        ]);
    }

    public function edit(Request $request, Design $design): View
    {
        Gate::authorize('update', $design);

        $design->load(['tags', 'media.productTemplate']);

        return view('pages.designer.designs.edit', [
            'design' => $design,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name']),
            'productTypes' => ProductTemplate::query()
                ->select('type')
                ->distinct()
                ->orderBy('type')
                ->pluck('type')
                ->all(),
        ]);
    }

    public function update(UpdateDesignerDesignRequest $request, Design $design, UpdateDesignAction $update, UploadMediaAction $upload): RedirectResponse
    {
        Gate::authorize('update', $design);

        $data = $request->validated();
        $mockup = $request->file('mockup');
        $printFile = $request->file('print_file');
        unset($data['mockup'], $data['print_file']);

        if (! empty($data)) {
            $update->execute($design, $data);
        }

        // Replace strategy: when the designer re-uploads a file, drop the old
        // row for that collection and store the new one. Keeps the one-file-per-
        // collection invariant that Mockup / PrintFile relations are built around.
        if ($mockup) {
            $this->replaceMedia($design, 'mockup', $mockup, $upload);
        }
        if ($printFile) {
            $this->replaceMedia($design, 'print_file', $printFile, $upload);
        }

        return redirect()
            ->route('designer.designs.show', $design)
            ->with('status', __('flash_design_updated'));
    }

    public function destroy(Request $request, Design $design, DeleteDesignAction $delete): RedirectResponse
    {
        Gate::authorize('delete', $design);

        $delete->execute($design);

        return redirect()
            ->route('designer.dashboard')
            ->with('status', __('flash_design_deleted'));
    }

    private function replaceMedia(Design $design, string $collection, UploadedFile $file, UploadMediaAction $upload): void
    {
        Media::query()
            ->forOwner($design)
            ->inCollection($collection)
            ->get()
            ->each(function (Media $media): void {
                Storage::disk('public')->delete($media->file_path);
                $media->delete();
            });

        $upload->execute($file, $design, $collection);
    }
}
