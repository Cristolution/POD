<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\DesignProductMapping;
use App\Models\PrinterProviderProfile;
use App\Notifications\PrinterUnavailableForMappingNotification;

/**
 * When a PrinterProviderProfile is removed (soft- or hard-delete), preserve
 * any DesignProductMapping rows that referenced it, null out the FK, and
 * notify each affected designer so they can pick a new fulfiller.
 *
 * We hook `deleting` (BEFORE the soft-delete UPDATE) and `forceDeleting`
 * (BEFORE the hard-delete DELETE) — not the post-event hooks — because the
 * DB-level `nullOnDelete()` FK constraint fires at DELETE time and nulls the
 * FK before any post-event handler runs. Capturing the mappings before the
 * DELETE means we still know which designers to notify.
 *
 * Without this, the FK alone would silently null the mappings and the
 * designer would have no record of what was assigned or why a product
 * dropped out of the catalog.
 */
final class PrinterProviderProfileObserver
{
    public function deleting(PrinterProviderProfile $printer): void
    {
        $this->detachAndNotify($printer);
    }

    public function forceDeleting(PrinterProviderProfile $printer): void
    {
        $this->detachAndNotify($printer);
    }

    private function detachAndNotify(PrinterProviderProfile $printer): void
    {
        $mappings = DesignProductMapping::query()
            ->where('preferred_printer_id', $printer->id)
            ->with('design.designer.user')
            ->get();

        if ($mappings->isEmpty()) {
            return;
        }

        // Null the FK in bulk so the next read reflects the new state. The
        // FK's nullOnDelete() will also do this at DELETE time, but doing
        // it here keeps behavior consistent regardless of DB engine.
        DesignProductMapping::query()
            ->where('preferred_printer_id', $printer->id)
            ->update(['preferred_printer_id' => null]);

        // Notify each designer once per printer-removal event. De-duplicate
        // so a designer with many affected mappings gets one notification,
        // not a flood.
        $seen = [];
        foreach ($mappings as $mapping) {
            $designer = $mapping->design?->designer;
            if ($designer === null || ! $designer->user) {
                continue;
            }

            $key = $designer->getKey();
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $designer->user->notify(new PrinterUnavailableForMappingNotification(
                mapping: $mapping,
                printer: $printer,
            ));
        }
    }
}
