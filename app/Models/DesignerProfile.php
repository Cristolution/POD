<?php

namespace App\Models;

use Database\Factories\DesignerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'bio', 'is_verified'])]
class DesignerProfile extends Model
{
    /** @use HasFactory<DesignerProfileFactory> */
    use HasFactory, HasUuids;

    /**
     * Pattern A: id is a CHAR(36) UUID.
     */
    public function uniqueIds(): array
    {
        return ['id'];
    }

    protected function casts(): array
    {
        return ['is_verified' => 'boolean'];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class, 'designer_id');
    }

    public function publishedDesigns(): HasMany
    {
        return $this->designs()
            ->where('status', 'published')
            ->whereNull('deleted_at');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeWithUserEmail($query, string $email)
    {
        return $query->whereHas('user', fn ($q) => $q->where('email', $email));
    }
}
