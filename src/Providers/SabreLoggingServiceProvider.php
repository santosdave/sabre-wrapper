<?php

namespace Santosdave\SabreWrapper\Providers;

use Illuminate\Support\ServiceProvider;
use Santosdave\SabreWrapper\Services\Logging\SabreLogger;
use Santosdave\SabreWrapper\Http\Middleware\SabreLoggingMiddleware;

class SabreLoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/logging.php',
            'sabre.logging'
        );

        // Register SabreLogger as singleton
        $this->app->singleton(SabreLogger::class, function ($app) {
            return new SabreLogger(config('sabre.logging'));
        });

        // Register logging middleware
        $this->app->singleton(SabreLoggingMiddleware::class, function ($app) {
            return new SabreLoggingMiddleware($app->make(SabreLogger::class));
        });
    }

    public function boot(): void
    {
        // Publish logging configuration
        $this->publishes([
            __DIR__ . '/../Config/logging.php' => config_path('sabre/logging.php'),
        ], 'sabre-config');

        // Register middleware alias
        $this->app['router']->aliasMiddleware('sabre.logging', SabreLoggingMiddleware::class);
    }

    public function provides(): array
    {
        return [
            SabreLogger::class,
            SabreLoggingMiddleware::class
        ];
    }
}