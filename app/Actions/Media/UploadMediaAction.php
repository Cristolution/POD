<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;
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

        return $media->refresh();
    }
}
