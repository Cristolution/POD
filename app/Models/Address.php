<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'line1', 'city', 'country', 'phone'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipping_address_id');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Render as a single-line string, useful for quick display
     * (e.g. "12 Rue de la Paix, Paris, France").
     */
    public function oneLine(): string
    {
        return collect([$this->line1, $this->city, $this->country])
            ->filter()
            ->implode(', ');
    }
}
