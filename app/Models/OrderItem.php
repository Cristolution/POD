<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'order_id',
    'design_product_mapping_id',
    'product_variant_id',
    'printer_provider_id',
    'status',
    'quantity',
    'unit_price',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function designProductMapping(): BelongsTo
    {
        return $this->belongsTo(DesignProductMapping::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function printerProvider(): BelongsTo
    {
        return $this->belongsTo(PrinterProviderProfile::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'order_id')
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

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['received', 'printing', 'printed', 'handed_off']);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function lineTotal(): float
    {
        return (float) $this->unit_price * $this->quantity;
    }

    public function isCancellable(): bool
    {
        return ! in_array($this->status, ['printed', 'handed_off', 'cancelled'], true);
    }
}
