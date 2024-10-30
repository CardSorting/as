<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreRateLimiter
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->route('store') ?? $request->route('subdomain');
        
        if (!$store) {
            return $next($request);
        }

        $key = 'store:' . $store . '|' . $request->ip();

        // Different limits for different endpoints
        $maxAttempts = match (true) {
            $request->routeIs('*.checkout') => 10,    // Checkout attempts
            $request->routeIs('*.download') => 100,   // File downloads
            default => 60,                            // General store access
        };

        // Decay time in minutes
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response->header('X-RateLimit-Limit', $maxAttempts)
            ->header('X-RateLimit-Remaining', $maxAttempts - $this->limiter->attempts($key));
    }
}
