<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'order_id',
    'method',
    'status',
    'confirmed_by_admin_id',
    'confirmed_at',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public function uniqueIds(): array
    {
        return ['id'];
    }

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function confirmedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_admin_id');
    }

    /**
     * Proof-of-payment image attachments.
     * Stored in the media table with collection_name = 'payment_proof'.
     */
    public function proofOfPayment(): MorphMany
    {
        return $this->media()->where('collection_name', 'payment_proof');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeForOrder($query, Order $order)
    {
        return $query->where('order_id', $order->id);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCashOnDelivery(): bool
    {
        return $this->method === 'cash_on_delivery';
    }

    public function isBankTransfer(): bool
    {
        return $this->method === 'bank_transfer';
    }

    public function isCard(): bool
    {
        return $this->method === 'card';
    }
}
