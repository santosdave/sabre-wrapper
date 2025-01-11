<?php

namespace Santosdave\SabreWrapper\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Santosdave\SabreWrapper\Services\Core\RateLimitService;
use Santosdave\SabreWrapper\Exceptions\SabreRateLimitException;

class RateLimitMiddleware
{
    private RateLimitService $rateLimiter;

    public function __construct(RateLimitService $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public function handle(Request $request, Closure $next)
    {
        $key = $this->getRateLimitKey($request);

        try {
            $this->rateLimiter->attempt($key);

            $response = $next($request);

            // Add rate limit headers to response
            $rateLimitInfo = $this->rateLimiter->getRateLimitInfo($key);
            return $this->addRateLimitHeaders($response, $rateLimitInfo);
        } catch (SabreRateLimitException $e) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => $e->getMessage(),
                'retry_after' => $e->getRetryAfter()
            ], 429)->withHeaders([
                'Retry-After' => $e->getRetryAfter(),
                'X-RateLimit-Reset' => $e->getReset()
            ]);
        }
    }

    private function getRateLimitKey(Request $request): string
    {
        // Extract the operation type from the request path
        $path = $request->path();
        $parts = explode('/', $path);

        $category = $parts[0] ?? 'default';
        $operation = $parts[1] ?? 'default';

        return "{$category}.{$operation}";
    }

    private function addRateLimitHeaders($response, array $rateLimitInfo): mixed
    {
        return $response->withHeaders([
            'X-RateLimit-Limit' => $rateLimitInfo['limit'],
            'X-RateLimit-Remaining' => $rateLimitInfo['remaining'],
            'X-RateLimit-Reset' => $rateLimitInfo['reset']
        ]);
    }
}
