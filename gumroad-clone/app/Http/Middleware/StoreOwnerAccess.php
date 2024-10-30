<?php

namespace App\Http\Middleware;

use App\Services\StoreIsolation\StoreConnection;
use Closure;
use Illuminate\Http\Request;

class StoreOwnerAccess
{
    private StoreConnection $connection;

    public function __construct(StoreConnection $connection)
    {
        $this->connection = $connection;
    }

    public function handle(Request $request, Closure $next)
    {
        $store = $this->connection->getCurrentStore();
        
        if (!$store || $store->silo->user_id !== auth()->id()) {
            abort(403, 'You do not have access to this store.');
        }

        return $next($request);
    }
}
