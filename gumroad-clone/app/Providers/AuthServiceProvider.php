<?php

namespace App\Providers;

use App\Models\StoreSilo;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        Gate::define('delete-store', function ($user, StoreSilo $store) {
            return $user->id === $store->user_id;
        });
    }
}
