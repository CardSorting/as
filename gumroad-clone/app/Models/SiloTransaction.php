<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiloTransaction extends Model
{
    protected $fillable = [
        'store_silo_id',
        'transaction_id',
        'amount',
        'type'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // Always use the main connection
        $this->setConnection(config('database.default'));
    }

    public function store()
    {
        return $this->belongsTo(StoreSilo::class, 'store_silo_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->setConnection(config('database.default'));
        });
    }
}
