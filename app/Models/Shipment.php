<?php

namespace App\Models;

use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'printer_provider_id',
    'delivery_company_id',
    'tracking_number',
    'status',
    'shipped_at',
    'delivered_at',
])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function printerProvider(): BelongsTo
    {
        return $this->belongsTo(PrinterProviderProfile::class);
    }

    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }

    /**
     * The order items that this shipment covers (logical grouping by
     * printer_provider_id, matching FK on order_items).
     */
    public function coveredItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id')
            ->where('printer_provider_id', $this->printer_provider_id);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForOrder($query, Order $order)
    {
        return $query->where('order_id', $order->id);
    }

    public function scopeForPrinter($query, PrinterProviderProfile $printer)
    {
        return $query->where('printer_provider_id', $printer->id);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Render a tracking URL using the linked delivery company's pattern.
     */
    public function trackingUrl(): ?string
    {
        return $this->deliveryCompany?->trackingUrlFor($this->tracking_number);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }
}
