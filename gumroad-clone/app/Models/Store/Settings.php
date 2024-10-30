<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    protected $table = 'store_settings';

    protected $fillable = [
        'theme_config',
        'payment_settings',
        'notification_settings',
    ];

    protected $casts = [
        'theme_config' => 'array',
        'payment_settings' => 'array',
        'notification_settings' => 'array',
    ];
}
