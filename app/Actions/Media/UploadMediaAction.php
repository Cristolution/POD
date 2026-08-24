<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class UploadMediaAction
{
    public function execute(UploadedFile $file, Model $owner, string $collectionName): Media
    {
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        $path = $file->store(
            "media/{$collectionName}",
            ['disk' => $disk],
        );

        $media = Media::create([
            'model_type' => $owner->getMorphClass(),
            'model_id' => (string) $owner->getKey(),
            'collection_name' => $collectionName,
            'file_path' => $path,
        ]);

        return $media->refresh();
    }
}
