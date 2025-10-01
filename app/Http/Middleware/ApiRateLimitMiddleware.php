<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Different rate limits for different operations
        $key = $this->resolveRequestSignature($request);

        // Bulk operations have stricter limits
        if ($this->isBulkOperation($request)) {
            $limit = 10; // 10 bulk operations per minute
        } elseif ($this->isWriteOperation($request)) {
            $limit = 60; // 60 write operations per minute
        } else {
            $limit = 200; // 200 read operations per minute
        }

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        RateLimiter::hit($key, 60); // 1-minute window

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', $limit - RateLimiter::attempts($key));

        return $response;
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Use IP address and user ID (if authenticated) for rate limiting
        $key = 'api-rate-limit:' . $request->ip();

        if ($request->user()) {
            $key .= ':user:' . $request->user()->id;
        }

        return $key;
    }

    /**
     * Determine if the request is a bulk operation
     */
    protected function isBulkOperation(Request $request): bool
    {
        // Check for bulk operations based on route or request data
        return str_contains($request->route()?->getName() ?? '', 'bulk') ||
               $request->has('bulk') ||
               (is_array($request->input('surveys')) && count($request->input('surveys', [])) > 1) ||
               (is_array($request->input('questions')) && count($request->input('questions', [])) > 1);
    }

    /**
     * Determine if the request is a write operation
     */
    protected function isWriteOperation(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);
    }
}
