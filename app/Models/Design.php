<?php

namespace App\Models;

use Database\Factories\DesignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['designer_id', 'category_id', 'title', 'status'])]
class Design extends Model
{
    /** @use HasFactory<DesignFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public function uniqueIds(): array
    {
        return ['id'];
    }

    /**
     * Force-delete cascade: when a Design row is permanently removed, its
     * DesignProductMapping rows must go with it. SQLite silently ignores
     * the `cascadeOnDelete()` FK declared in the migration (it requires
     * inline `REFERENCES` in the CREATE TABLE), so we explicitly cascade
     * at the Eloquent layer. MySQL/Postgres honor the FK as a safety net.
     */
    protected static function booted(): void
    {
        static::forceDeleting(function (Design $design): void {
            $design->mappings()->get()->each(static fn ($mapping) => $mapping->forceDelete());
        });
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function designer(): BelongsTo
    {
        return $this->belongsTo(DesignerProfile::class, 'designer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        // design_tag is a pure junction table — no created_at/updated_at columns.
        return $this->belongsToMany(Tag::class, 'design_tag');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(DesignProductMapping::class);
    }

    public function publishedMappings(): HasMany
    {
        return $this->mappings()->whereHas('design', fn ($q) => $q->where('status', 'published'));
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DesignReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->approved();
    }

    public function reviewBy(User $user): ?DesignReview
    {
        return $this->reviews()
            ->where('customer_id', $user->getKey())
            ->first();
    }

    /**
     * Polymorphic media: mockups, print files, etc.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function mockups(): MorphMany
    {
        return $this->media()->where('collection_name', 'mockup');
    }

    public function printFiles(): MorphMany
    {
        return $this->media()->where('collection_name', 'print_file');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    public function scopeForDesigner(Builder $query, DesignerProfile $designer): Builder
    {
        return $query->where('designer_id', $designer->id);
    }

    public function scopeInCategory(Builder $query, Category $category): Builder
    {
        return $query->where('category_id', $category->id);
    }

    public function scopeWithTag(Builder $query, Tag $tag): Builder
    {
        return $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Average rating across approved reviews (1–5). Null when no reviews.
     */
    public function averageRating(): ?float
    {
        $avg = $this->approvedReviews()->avg('rating');

        return $avg === null ? null : round((float) $avg, 1);
    }
}
