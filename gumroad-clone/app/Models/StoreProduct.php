<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_container_id',
        'name',
        'description',
        'price',
        'currency',
        'files',
        'is_digital',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'files' => 'array',
        'is_digital' => 'boolean',
    ];

    public function storeContainer(): BelongsTo
    {
        return $this->belongsTo(StoreContainer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class);
    }

    public function getFormattedPrice(): string
    {
        return "{$this->currency} " . number_format($this->price, 2);
    }
}
