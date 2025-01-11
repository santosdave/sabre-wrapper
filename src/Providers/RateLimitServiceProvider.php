<?php

namespace Santosdave\Sabre\Providers;

use Illuminate\Support\ServiceProvider;
use Santosdave\Sabre\Services\Core\RateLimitService;
use Santosdave\Sabre\Http\Middleware\RateLimitMiddleware;

class RateLimitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RateLimitService::class, function ($app) {
            return new RateLimitService();
        });

        $this->app->singleton(RateLimitMiddleware::class, function ($app) {
            return new RateLimitMiddleware($app->make(RateLimitService::class));
        });
    }

    public function boot(): void
    {
        // Register the middleware
        $this->app['router']->aliasMiddleware('sabre.ratelimit', RateLimitMiddleware::class);

        // Publish the configuration
        $this->publishes([
            __DIR__ . '/../Config/rate-limiting.php' => config_path('sabre/rate-limiting.php'),
        ], 'sabre-config');

        // Merge the configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/rate-limiting.php',
            'sabre.rate_limiting'
        );
    }

    public function provides(): array
    {
        return [
            RateLimitService::class,
            RateLimitMiddleware::class,
        ];
    }
}