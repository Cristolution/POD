<?php

namespace App\Models;

use Database\Factories\DeliveryCompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'coverage_zones', 'tracking_url_pattern'])]
class DeliveryCompany extends Model
{
    /** @use HasFactory<DeliveryCompanyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'coverage_zones' => 'array',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Render a tracking URL for a given tracking number, using the
     * company's tracking_url_pattern (e.g. https://acme.com/track/{number}).
     * Returns null if the company has no pattern.
     */
    public function trackingUrlFor(?string $trackingNumber): ?string
    {
        if (! $this->tracking_url_pattern || ! $trackingNumber) {
            return null;
        }

        return str_replace('{number}', $trackingNumber, $this->tracking_url_pattern);
    }
}
