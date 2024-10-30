<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_container_id',
        'store_product_id',
        'order_number',
        'amount',
        'currency',
        'status',
        'customer_details',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'customer_details' => 'array',
        'paid_at' => 'datetime',
    ];

    public function storeContainer(): BelongsTo
    {
        return $this->belongsTo(StoreContainer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class, 'store_product_id');
    }

    public function siloTransaction(): BelongsTo
    {
        return $this->belongsTo(SiloTransaction::class, 'order_number', 'transaction_id');
    }

    protected static function booted()
    {
        // When an order is marked as paid, create a silo transaction
        static::updated(function ($order) {
            if ($order->isDirty('status') && $order->status === 'paid' && $order->paid_at) {
                $order->storeContainer->silo->transactions()->create([
                    'transaction_id' => $order->order_number,
                    'amount' => $order->amount,
                    'transaction_date' => $order->paid_at,
                    'is_paid' => false,
                ]);
            }
        });
    }
}
