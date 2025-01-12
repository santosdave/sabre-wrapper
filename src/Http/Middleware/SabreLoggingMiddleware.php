<?php

namespace Santosdave\SabreWrapper\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Santosdave\SabreWrapper\Services\Logging\SabreLogger;

class SabreLoggingMiddleware
{
    private SabreLogger $logger;

    public function __construct(SabreLogger $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Request $request, Closure $next)
    {
        // Start timing
        $startTime = microtime(true);

        // Set request context
        $this->logger->setContext([
            'request_id' => $request->header('X-Request-ID', uniqid()),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Extract service and action from request path
        $path = $request->path();
        $pathParts = explode('/', $path);
        $service = $pathParts[0] ?? 'unknown';
        $action = $pathParts[1] ?? 'unknown';

        // Log request
        $this->logger->logRequest($service, $action, [
            'method' => $request->method(),
            'path' => $path,
            'headers' => $request->headers->all(),
            'params' => $request->all()
        ]);

        try {
            // Handle request
            $response = $next($request);

            // Calculate duration
            $duration = microtime(true) - $startTime;

            // Log response
            $this->logger->logResponse($service, $action, [
                'status' => $response->status(),
                'headers' => $response->headers->all(),
                'content' => $response->getContent()
            ], $duration);

            return $response;
        } catch (\Throwable $e) {
            // Log error
            $this->logger->logError($e);
            throw $e;
        } finally {
            // Clear context
            $this->logger->clearContext();
        }
    }
}