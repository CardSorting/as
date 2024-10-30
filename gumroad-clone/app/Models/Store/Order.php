<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;
use App\Services\Billing\PaymentProcessor;
use App\Models\StoreSilo;

class Order extends Model
{
    protected $fillable = [
        'product_id',
        'order_number',
        'amount',
        'status',
        'customer_details',
        'payment_details',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'customer_details' => 'array',
        'payment_details' => 'array',
        'paid_at' => 'datetime'
    ];

    private ?StoreSilo $storeSilo = null;

    public function setStoreSilo(StoreSilo $store): void
    {
        $this->storeSilo = $store;
    }

    public function getStoreSilo(): ?StoreSilo
    {
        return $this->storeSilo;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function processPayment(array $paymentDetails): void
    {
        if (!$this->storeSilo) {
            throw new \RuntimeException('Store not set for order');
        }

        app(PaymentProcessor::class)->processPayment($this, $paymentDetails);
    }
}
