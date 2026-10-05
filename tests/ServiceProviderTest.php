<?php

namespace Santosdave\SabreWrapper\Tests;

use Illuminate\Support\ServiceProvider;
use Santosdave\SabreWrapper\SabreServiceProvider;
use Santosdave\SabreWrapper\Services\ServiceFactory;

class ServiceProviderTest extends TestCase
{
    public function test_the_config_can_be_published(): void
    {
        $paths = ServiceProvider::pathsToPublish(SabreServiceProvider::class, 'sabre-config');

        $this->assertCount(1, $paths);
        $this->assertFileExists(array_key_first($paths));
    }

    public function test_the_package_config_is_merged_and_services_resolve(): void
    {
        $this->assertSame('v3', config('sabre.auth.version.rest'));
        $this->assertInstanceOf(ServiceFactory::class, app(ServiceFactory::class));
    }
}
