<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'customer_id',
    'shipping_address_id',
    'shipping_line1',
    'shipping_city',
    'shipping_country',
    'shipping_phone',
    'status',
    'total_amount',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public function uniqueIds(): array
    {
        return ['id'];
    }

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function confirmedPayment(): ?Payment
    {
        return $this->payments()->where('status', 'confirmed')->latest()->first();
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForCustomer($query, User $user)
    {
        return $query->where('customer_id', $user->id);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOpen($query)
    {
        // Anything not yet delivered or cancelled
        return $query->whereNotIn('status', ['delivered', 'cancelled']);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Group items by their printer — convenient for split fulfilment views.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Database\Eloquent\Collection<int, OrderItem>>
     */
    public function itemsByPrinter()
    {
        return $this->items()
            ->with('printerProvider')
            ->get()
            ->groupBy(fn ($item) => $item->printer_provider_id);
    }

    /**
     * Heuristic customer-facing status derived from child item statuses.
     * Per SRS FR-8.7, the stored `orders.status` is the source of truth at
     * query time; this is useful for dashboards.
     */
    public function derivedStatus(): string
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            return $this->status;
        }

        if ($items->every(fn ($i) => $i->status === 'cancelled')) {
            return 'cancelled';
        }

        if ($items->every(fn ($i) => in_array($i->status, ['printed', 'handed_off']))) {
            return 'shipped';
        }

        if ($items->contains(fn ($i) => in_array($i->status, ['printing', 'printed', 'handed_off']))) {
            return 'processing';
        }

        return $this->status;
    }
}
