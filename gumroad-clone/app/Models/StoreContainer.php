<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreContainer extends Model
{
    use HasFactory;

    protected $fillable = [
        'silo_id',
        'subdomain',
        'custom_domain',
        'settings',
        'theme_config',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'theme_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function silo(): BelongsTo
    {
        return $this->belongsTo(StoreSilo::class, 'silo_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(StoreProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class);
    }

    public function getDomainUrl(): string
    {
        return $this->custom_domain ?? "{$this->subdomain}." . config('app.domain');
    }

    public function getStoreUrl(): string
    {
        return "https://" . $this->getDomainUrl();
    }
}
