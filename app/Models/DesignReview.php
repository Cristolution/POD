<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DesignReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['design_id', 'customer_id', 'order_id', 'rating', 'title', 'body', 'is_approved'])]
class DesignReview extends Model
{
    /** @use HasFactory<DesignReviewFactory> */
    use HasFactory;

    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeForDesign($query, Design $design)
    {
        return $query->where('design_id', $design->id);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function stars(): string
    {
        return str_repeat('★', $this->rating).str_repeat('☆', self::MAX_RATING - $this->rating);
    }
}
