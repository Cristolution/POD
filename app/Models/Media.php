<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['model_type', 'model_id', 'collection_name', 'product_template_id', 'file_path'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    /**
     * Polymorphic owner. Any model that owns files (Design, Payment, etc.)
     * declares a `morphMany(Media::class, 'model')` relationship.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Optional product-template scope — when set, this media row is the
     * customer-facing preview for that specific product (e.g. "design X
     * on a t-shirt"). When null, the media row is the design's default
     * mockup, used everywhere no per-product override exists.
     */
    public function productTemplate(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForOwner($query, Model $owner)
    {
        return $query->where('model_type', $owner->getMorphClass())
            ->where('model_id', $owner->getKey());
    }

    public function scopeInCollection($query, string $collection)
    {
        return $query->where('collection_name', $collection);
    }

    public function scopeDefaultMockups($query)
    {
        // The design's general-purpose mockup — used everywhere no
        // per-product override exists.
        return $query->where('collection_name', 'mockup')
            ->whereNull('product_template_id');
    }

    public function scopeProductMockups($query)
    {
        // Per-product overrides — one per (design, product_template).
        return $query->where('collection_name', 'mockup')
            ->whereNotNull('product_template_id');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function isMockup(): bool
    {
        return $this->collection_name === 'mockup';
    }

    public function isDefaultMockup(): bool
    {
        return $this->isMockup() && $this->product_template_id === null;
    }

    public function isProductMockup(): bool
    {
        return $this->isMockup() && $this->product_template_id !== null;
    }

    public function isPrintFile(): bool
    {
        return $this->collection_name === 'print_file';
    }

    public function isPaymentProof(): bool
    {
        return $this->collection_name === 'payment_proof';
    }

    public function isAttachment(): bool
    {
        return $this->collection_name === 'attachment';
    }
}
