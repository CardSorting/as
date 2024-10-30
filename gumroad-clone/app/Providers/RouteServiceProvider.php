<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // API Routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Admin Routes
            Route::middleware('web')
                ->prefix('admin')
                ->group(base_path('routes/admin.php'));

            // Store Routes with domain handling
            Route::middleware('web')
                ->domain('{subdomain}.' . config('app.domain'))
                ->group(base_path('routes/store.php'));

            // Main web Routes
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
