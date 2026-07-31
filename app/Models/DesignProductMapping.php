<?php

namespace App\Models;

use Database\Factories\DesignProductMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['design_id', 'product_template_id', 'preferred_printer_id', 'final_price'])]
class DesignProductMapping extends Model
{
    /** @use HasFactory<DesignProductMappingFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public function uniqueIds(): array
    {
        return ['id'];
    }

    protected function casts(): array
    {
        return [
            'final_price' => 'decimal:2',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function productTemplate(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class);
    }

    /**
     * Designer's preferred fulfiller. Informational in v1.2 — the application
     * layer validates that the printer can actually fulfil this combination.
     */
    public function preferredPrinter(): BelongsTo
    {
        return $this->belongsTo(PrinterProviderProfile::class, 'preferred_printer_id');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForDesign($query, Design $design)
    {
        return $query->where('design_id', $design->id);
    }

    public function scopeForTemplate($query, ProductTemplate $template)
    {
        return $query->where('product_template_id', $template->id);
    }

    public function scopeForPreferredPrinter($query, PrinterProviderProfile $printer)
    {
        return $query->where('preferred_printer_id', $printer->id);
    }

    public function scopePublished($query)
    {
        return $query->whereHas('design', fn ($q) => $q->published());
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Effective price for a customer, including optional variant price_delta.
     */
    public function customerPriceFor(?ProductVariant $variant = null): float
    {
        return (float) $this->final_price + (float) ($variant?->price_delta ?? 0);
    }
}
