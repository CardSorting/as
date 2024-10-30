<?php

namespace App\Http\Middleware;

use App\Services\StoreIsolation\StoreConnection;
use Closure;
use Illuminate\Http\Request;

class StoreConnectionMiddleware
{
    private $storeConnection;

    public function __construct(StoreConnection $storeConnection)
    {
        $this->storeConnection = $storeConnection;
    }

    public function handle(Request $request, Closure $next)
    {
        $storeDomain = $request->getHost();
        $store = $this->storeConnection->resolveStore($storeDomain);
        
        if (!$store) {
            abort(404, 'Store not found');
        }

        $this->storeConnection->connect($store);
        
        return $next($request);
    }
}
