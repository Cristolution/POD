<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Media\DeleteMediaAction;
use App\Actions\Media\UploadMediaAction;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    private const OWNER_MODEL_MAP = [
        'design' => Design::class,
        'payment' => Payment::class,
        'designer_profile' => DesignerProfile::class,
        'printer_provider_profile' => PrinterProviderProfile::class,
        'order' => Order::class,
        'order_item' => OrderItem::class,
        'product_template' => ProductTemplate::class,
        'product_variant' => ProductVariant::class,
        'user' => User::class,
    ];

    public function __construct(
        private readonly UploadMediaAction $upload,
        private readonly DeleteMediaAction $delete,
    ) {}

    public function show(Media $media): JsonResponse
    {
        $this->authorize('view', $media);

        return response()->json([
            'data' => $this->transform($media),
        ]);
    }

    public function store(StoreMediaRequest $request): JsonResponse
    {
        $this->authorize('create', Media::class);

        $owner = $this->resolveOwner(
            $request->string('model_type')->value(),
            $request->string('model_id')->value(),
        );

        $media = $this->upload->execute(
            $request->file('file'),
            $owner,
            $request->string('collection_name')->value(),
        );

        return response()->json(
            ['data' => $this->transform($media)],
            201,
        );
    }

    public function destroy(Media $media): JsonResponse
    {
        $this->authorize('delete', $media);

        $this->delete->execute($media);

        return response()->json(null, 204);
    }

    /**
     * Resolve the polymorphic owner short name (e.g. `design`, `payment`,
     * `designer_profile`) into a model class and load the row.
     */
    private function resolveOwner(string $modelType, string $modelId): Model
    {
        $key = Str::snake($modelType);

        if (! isset(self::OWNER_MODEL_MAP[$key])) {
            abort(404, "Unknown model_type '{$modelType}'.");
        }

        $class = self::OWNER_MODEL_MAP[$key];

        $instance = $class::query()->where('id', $modelId)->first();

        if (! $instance instanceof Model) {
            abort(404, "Owner of type {$modelType} with id {$modelId} not found.");
        }

        return $instance;
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Media $media): array
    {
        $payload = (new MediaResource($media))->resolve(request());

        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        $payload['url'] = Storage::disk($disk)->url($media->file_path);

        return $payload;
    }
}
