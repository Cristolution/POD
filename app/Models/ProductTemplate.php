<?php

namespace App\Models;

use Database\Factories\ProductTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['printer_provider_id', 'name', 'type', 'base_cost', 'specs'])]
class ProductTemplate extends Model
{
    /** @use HasFactory<ProductTemplateFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public function uniqueIds(): array
    {
        return ['id'];
    }

    protected function casts(): array
    {
        return [
            'base_cost' => 'decimal:2',
            'specs' => 'array',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function printerProvider(): BelongsTo
    {
        return $this->belongsTo(PrinterProviderProfile::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function designProductMappings(): HasMany
    {
        return $this->hasMany(DesignProductMapping::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForPrinter($query, PrinterProviderProfile $printer)
    {
        return $query->where('printer_provider_id', $printer->id);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Effective price for a given variant (template base_cost + variant price_delta).
     */
    public function priceForVariant(?ProductVariant $variant): float
    {
        return (float) $this->base_cost + (float) ($variant?->price_delta ?? 0);
    }
}
