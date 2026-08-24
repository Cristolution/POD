<?php

namespace App\Models;

use App\Http\Middleware\EnsureAdmin;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'phone', 'role'])]
#[Hidden(['password'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * Pattern A: the UUID IS the primary key. No separate `uuid` column.
     */
    public function uniqueIds(): array
    {
        return ['id'];
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // ------------------------------------------------------------------
    // Profiles (1:1, role-gated)
    // ------------------------------------------------------------------

    public function designerProfile(): HasOne
    {
        return $this->hasOne(DesignerProfile::class);
    }

    public function printerProviderProfile(): HasOne
    {
        return $this->hasOne(PrinterProviderProfile::class);
    }

    // ------------------------------------------------------------------
    // Address book
    // ------------------------------------------------------------------

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    // ------------------------------------------------------------------
    // Cart / Orders
    // ------------------------------------------------------------------

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function cart()
    {
        // Convenience: get nested cart with mapping + variant + relations.
        return $this->cartItems()->with(['designProductMapping.design', 'productVariant']);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    // ------------------------------------------------------------------
    // Payments (admin-confirmed)
    // ------------------------------------------------------------------

    public function confirmedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'confirmed_by_admin_id');
    }

    // ------------------------------------------------------------------
    // Notifications
    // ------------------------------------------------------------------

    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('role', 'customer');
    }

    public function scopeDesigners(Builder $query): Builder
    {
        return $query->where('role', 'designer')->whereHas('designerProfile');
    }

    public function scopePrinterProviders(Builder $query): Builder
    {
        return $query->where('role', 'printer_provider')->whereHas('printerProviderProfile');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDesigner(): bool
    {
        return $this->role === 'designer';
    }

    public function isPrinterProvider(): bool
    {
        return $this->role === 'printer_provider';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Filament v4 panel access gate. Returns true so any authenticated user
     * can reach the panel — the {@see EnsureAdmin}
     * middleware then 403s anyone whose role is not `admin`. Per-panel
     * gating can be added here by inspecting `$panel->getId()`.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
