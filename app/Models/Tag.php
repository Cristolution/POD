<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function designs(): BelongsToMany
    {
        return $this->belongsToMany(Design::class, 'design_tag')
            ->withTimestamps();
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopePopular($query)
    {
        return $query->withCount('designs')
            ->orderByDesc('designs_count');
    }
}
