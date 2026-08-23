<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'phone', 'role'])]
#[Hidden(['password'])]
class User extends Authenticatable
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

    public function notifications(): HasMany
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
}
