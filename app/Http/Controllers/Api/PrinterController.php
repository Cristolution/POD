<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Printer\DeletePrinterProfileAction;
use App\Actions\Printer\StorePrinterProfileAction;
use App\Actions\Printer\UpdatePrinterProfileAction;
use App\Http\Requests\Printer\StorePrinterProfileRequest;
use App\Http\Requests\Printer\UpdatePrinterProfileRequest;
use App\Http\Resources\PrinterProviderProfileResource;
use App\Models\PrinterProviderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrinterController extends Controller
{
    public function __construct(
        private readonly StorePrinterProfileAction $store,
        private readonly UpdatePrinterProfileAction $update,
        private readonly DeletePrinterProfileAction $delete,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = PrinterProviderProfile::query()->with(['user', 'productTemplates']);
        if ($userId = $request->string('user_id')->value()) {
            $query->where('user_id', $userId);
        }

        return response()->json($this->paginated($query));
    }

    public function show(PrinterProviderProfile $printer): JsonResponse
    {
        return response()->json([
            'data' => new PrinterProviderProfileResource(
                $printer->load(['user', 'productTemplates'])->loadCount('productTemplates'),
            ),
        ]);
    }

    public function store(StorePrinterProfileRequest $request): JsonResponse
    {
        $profile = $this->store->execute($request->user(), $request->validated());

        return response()->json(['data' => new PrinterProviderProfileResource($profile)], 201);
    }

    public function updateMe(UpdatePrinterProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->printerProviderProfile ?? abort(404, 'Printer profile not found.');
        $this->authorize('update', $profile);
        $updated = $this->update->execute($profile, $request->validated());

        return response()->json(['data' => new PrinterProviderProfileResource($updated)]);
    }

    public function destroyMe(Request $request): JsonResponse
    {
        $profile = $request->user()->printerProviderProfile ?? abort(404, 'Printer profile not found.');
        $this->authorize('delete', $profile);
        $this->delete->execute($profile);

        return response()->json(null, 204);
    }
}
