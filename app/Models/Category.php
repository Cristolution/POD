<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'parent_id'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    // ------------------------------------------------------------------
    // Relationships (self-referencing tree)
    // ------------------------------------------------------------------

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * All ancestor IDs up to the root. Useful for breadcrumb / facade queries.
     */
    public function ancestorIds(): array
    {
        $ids = [];
        $current = $this->parent;

        while ($current) {
            $ids[] = $current->id;
            $current = $current->parent;
        }

        return array_reverse($ids);
    }
}
