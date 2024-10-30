<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSilo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_domain',
        'subscription_tier',
        'payment_status',
        'monthly_fee',
        'subscription_limits',
        'next_billing_date',
        'timezone',
        'stripe_account_id',
        'payout_method_valid',
        'available_balance',
        'held_balance',
        'revenue_share_percentage'
    ];

    protected $casts = [
        'subscription_limits' => 'array',
        'next_billing_date' => 'datetime',
        'monthly_fee' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'held_balance' => 'decimal:2',
        'payout_method_valid' => 'boolean',
        'revenue_share_percentage' => 'decimal:2'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getCurrentLimits(): array
    {
        return $this->subscription_limits;
    }

    public function getAvailableBalance(): float
    {
        return $this->available_balance;
    }

    public function getHeldBalance(): float
    {
        return $this->held_balance;
    }

    public function isPaymentActive(): bool
    {
        return $this->payment_status === 'active';
    }

    public function getDatabaseName(): string
    {
        return "store_{$this->id}";
    }
}
