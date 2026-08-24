<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Admin\ComputePlatformOverviewAction;
use App\Actions\Admin\ItemsWithoutShipmentAction;
use App\Actions\Admin\ListDeletedAuditAction;
use App\Actions\Admin\OrphanedMediaAction;
use App\Actions\Admin\ProfileMismatchesAction;
use App\Actions\Admin\StuckCartItemsAction;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function __construct(
        private readonly ComputePlatformOverviewAction $overview,
        private readonly ListDeletedAuditAction $audit,
        private readonly ProfileMismatchesAction $profileMismatches,
        private readonly OrphanedMediaAction $orphanedMedia,
        private readonly ItemsWithoutShipmentAction $itemsWithoutShipment,
        private readonly StuckCartItemsAction $stuckCartItems,
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json($this->overview->execute());
    }

    public function auditDeleted(): JsonResponse
    {
        return response()->json($this->audit->execute());
    }

    public function profileMismatches(): JsonResponse
    {
        return response()->json($this->profileMismatches->execute());
    }

    public function orphanedMedia(): JsonResponse
    {
        return response()->json(['data' => $this->orphanedMedia->execute()]);
    }

    public function itemsWithoutShipment(): JsonResponse
    {
        return response()->json(['data' => $this->itemsWithoutShipment->execute()]);
    }

    public function stuckCartItems(): JsonResponse
    {
        return response()->json(['data' => $this->stuckCartItems->execute()]);
    }
}
