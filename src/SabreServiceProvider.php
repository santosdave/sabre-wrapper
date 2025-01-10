<?php

namespace Santosdave\Sabre;

use Illuminate\Support\ServiceProvider;
use Santosdave\Sabre\Services\Auth\SabreAuthenticator;
use Santosdave\Sabre\Services\ServiceFactory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Santosdave\Sabre\Contracts\Services\QueueServiceInterface;

class SabreServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/Config/sabre.php',
            'sabre'
        );

        // Register the authenticator
        $this->app->singleton(SabreAuthenticator::class, function ($app) {
            return new SabreAuthenticator(
                config('sabre.credentials.username'),
                config('sabre.credentials.password'),
                config('sabre.credentials.pcc'),
                config('sabre.environment'),
                config('sabre.credentials.client_id'),
                config('sabre.credentials.client_secret')
            );
        });

        // Register the service factory
        $this->app->singleton(ServiceFactory::class, function ($app) {
            return new ServiceFactory(
                $app->make(SabreAuthenticator::class),
                config('sabre.environment')
            );
        });

        // Register logger
        $this->app->singleton('sabre.logger', function ($app) {
            $logger = new Logger('sabre');

            if (config('sabre.logging.enabled', true)) {
                $logger->pushHandler(
                    new StreamHandler(
                        storage_path('logs/sabre.log'),
                        config('sabre.logging.level', Logger::DEBUG)
                    )
                );
            }

            return $logger;
        });

        $this->app->bind(QueueServiceInterface::class, function ($app) {
            return $app->make(ServiceFactory::class)->createQueueService(
                config('sabre.default_service_type', ServiceFactory::REST)
            );
        });

        // Register main Sabre facade
        $this->app->singleton('sabre', function ($app) {
            return new Sabre(
                $app->make(ServiceFactory::class),
                $app->make('sabre.logger')
            );
        });
    }



    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/Config/sabre.php' => config_path('sabre.php'),
        ], 'sabre-config');
    }
}
