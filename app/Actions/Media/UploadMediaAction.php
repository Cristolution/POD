<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Design;
use App\Models\DesignProductMapping;
use App\Models\Media;
use App\Models\ProductTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class UploadMediaAction
{
    /**
     * @param  string|null  $productTemplateId  When set (and collection_name='mockup'),
     *                                          the upload is scoped to that product template
     *                                          — a per-product override shown on the storefront
     *                                          design page when the customer is browsing that
     *                                          product type.
     */
    public function execute(
        UploadedFile $file,
        Model $owner,
        string $collectionName,
        ?string $productTemplateId = null,
    ): Media {
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        // Per-product mockups go in their own folder so they don't collide
        // with the design's default mockup or print file. Path layout:
        //   media/mockup/<file>                  ← default mockup
        //   media/mockup/<product_id>/<file>      ← per-product mockup
        //   media/print_file/<file>               ← print file
        $folder = "media/{$collectionName}";
        if ($collectionName === 'mockup' && $productTemplateId !== null) {
            $folder = "media/{$collectionName}/{$productTemplateId}";
        }

        $path = $file->store(
            $folder,
            ['disk' => $disk],
        );

        $media = Media::create([
            'model_type' => $owner->getMorphClass(),
            'model_id' => (string) $owner->getKey(),
            'collection_name' => $collectionName,
            'product_template_id' => $productTemplateId,
            'file_path' => $path,
        ]);

        // INTEGRITY INVARIANT: a per-product mockup on a Design must always
        // have a corresponding DesignProductMapping(design_id, product_template_id).
        // Otherwise the storefront shows a per-product preview the customer
        // can't actually order — a "ghost" mockup. We lazily firstOrCreate the
        // mapping here so the invariant holds whenever a per-product mockup lands.
        if (
            $collectionName === 'mockup'
            && $productTemplateId !== null
            && $owner instanceof Design
        ) {
            $template = ProductTemplate::find($productTemplateId);
            if ($template !== null) {
                DesignProductMapping::firstOrCreate(
                    [
                        'design_id' => $owner->getKey(),
                        'product_template_id' => $productTemplateId,
                    ],
                    [
                        'preferred_printer_id' => $template->printer_provider_id,
                        // Don't set final_price if a designer-priced mapping
                        // already exists — let that win. firstOrCreate only
                        // populates the values dict when creating.
                        'final_price' => $template->base_cost * 2.5,
                    ],
                );
            }
        }

        return $media->refresh();
    }
}
