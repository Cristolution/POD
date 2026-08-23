<?php

namespace App\Models;

use Database\Factories\PrinterProviderProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'company_name'])]
class PrinterProviderProfile extends Model
{
    /** @use HasFactory<PrinterProviderProfileFactory> */
    use HasFactory, HasUuids;

    public function uniqueIds(): array
    {
        return ['id'];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productTemplates(): HasMany
    {
        return $this->hasMany(ProductTemplate::class, 'printer_provider_id');
    }

    public function preferredDesignProductMappings(): HasMany
    {
        return $this->hasMany(DesignProductMapping::class, 'preferred_printer_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function pendingOrderItems(): HasMany
    {
        return $this->orderItems()->whereIn('status', ['pending', 'received']);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeWithMinBaseCost($query, float $min)
    {
        return $query->whereHas('productTemplates', fn ($q) => $q->where('base_cost', '>=', $min));
    }
}
