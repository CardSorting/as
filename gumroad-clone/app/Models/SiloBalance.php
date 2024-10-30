<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiloBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_silo_id',
        'current_balance',
        'lifetime_earnings',
        'last_transaction_at',
        'last_payout_at',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'lifetime_earnings' => 'decimal:2',
        'last_transaction_at' => 'datetime',
        'last_payout_at' => 'datetime',
    ];

    public function storeSilo(): BelongsTo
    {
        return $this->belongsTo(StoreSilo::class);
    }

    public function needsPayout(): bool
    {
        return $this->current_balance >= 100.00 &&
               ($this->last_payout_at === null || 
                $this->last_payout_at->diffInDays(now()) >= 30);
    }
}
