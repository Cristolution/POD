<?php

namespace App\Models;

use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'design_product_mapping_id', 'product_variant_id', 'quantity'])]
class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function designProductMapping(): BelongsTo
    {
        return $this->belongsTo(DesignProductMapping::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Effective unit price for this line: mapping.final_price + variant.price_delta.
     */
    public function unitPrice(): float
    {
        return $this->designProductMapping?->customerPriceFor($this->productVariant) ?? 0.0;
    }

    /**
     * Line total: unit price * quantity.
     */
    public function lineTotal(): float
    {
        return $this->unitPrice() * $this->quantity;
    }

    /**
     * The printer that will fulfil this line (the one that owns the product_template).
     */
    public function printerProvider(): ?PrinterProviderProfile
    {
        return $this->designProductMapping?->productTemplate?->printerProvider;
    }

    /**
     * Application-layer upsert: merge quantities on the existing line
     * rather than failing on the unique constraint. Caps the result at
     * 100 so repeated add-to-cart clicks can't overflow the per-line limit.
     */
    public function mergeQuantity(int $additional): void
    {
        $this->update(['quantity' => min($this->quantity + $additional, 100)]);
    }
}
