<?php

namespace Santosdave\SabreWrapper\Providers;

use Illuminate\Support\ServiceProvider;
use Santosdave\SabreWrapper\Services\Auth\SabreAuthenticator;
use Santosdave\SabreWrapper\Services\Auth\TokenRotator;
use Santosdave\SabreWrapper\Services\Auth\SessionManager;
use Santosdave\SabreWrapper\Services\Auth\DistributedLockService;
use Santosdave\SabreWrapper\Services\Auth\AuthenticationRetryHandler;
use Santosdave\SabreWrapper\Contracts\Auth\TokenManagerInterface;
use Santosdave\SabreWrapper\Contracts\Auth\SessionManagerInterface;
use Santosdave\SabreWrapper\Contracts\Auth\TokenRotatorInterface;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register core authentication services
        $this->app->singleton(DistributedLockService::class);
        $this->app->singleton(AuthenticationRetryHandler::class);

        // Bind interfaces to implementations
        $this->app->bind(TokenRotatorInterface::class, TokenRotator::class);
        $this->app->bind(SessionManagerInterface::class, SessionManager::class);
        
        // Register main authenticator
        $this->app->singleton(TokenManagerInterface::class, function ($app) {
            return new SabreAuthenticator(
                config('sabre.credentials.username'),
                config('sabre.credentials.password'),
                config('sabre.credentials.pcc'),
                config('sabre.environment'),
                config('sabre.credentials.client_id'),
                config('sabre.credentials.client_secret')
            );
        });

        // Bind SabreAuthenticator as concrete implementation
        $this->app->bind(SabreAuthenticator::class, function ($app) {
            return $app->make(TokenManagerInterface::class);
        });
    }

    public function provides(): array
    {
        return [
            TokenManagerInterface::class,
            SessionManagerInterface::class,
            TokenRotatorInterface::class,
            DistributedLockService::class,
            AuthenticationRetryHandler::class,
            SabreAuthenticator::class
        ];
    }
}