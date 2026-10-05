<?php

namespace Santosdave\SabreWrapper\Tests;

use GuzzleHttp\HandlerStack;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Santosdave\SabreWrapper\Http\HttpClientFactory;
use Santosdave\SabreWrapper\SabreServiceProvider;

/**
 * A Laravel app with the package, fake credentials and no network: Sabre is FakeSabre.
 */
abstract class TestCase extends BaseTestCase
{
    protected FakeSabre $sabre;

    protected function getPackageProviders($app): array
    {
        return [SabreServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('sabre.environment', 'cert');
        $app['config']->set('sabre.endpoints.cert.rest', 'https://sabre.test');
        $app['config']->set('sabre.credentials', [
            'username' => 'test-user',
            'password' => 'test-password',
            'pcc' => 'TPCC',
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
        ]);
        $app['config']->set('sabre.request.retries', 1);
        $app['config']->set('sabre.request.retry_delay', 0);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->sabre = new FakeSabre;
        $this->app->instance(HttpClientFactory::class, new HttpClientFactory(HandlerStack::create($this->sabre)));
    }
}
