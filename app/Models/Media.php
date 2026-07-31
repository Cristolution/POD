<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['model_type', 'model_id', 'collection_name', 'file_path'])]
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

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function isMockup(): bool
    {
        return $this->collection_name === 'mockup';
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
