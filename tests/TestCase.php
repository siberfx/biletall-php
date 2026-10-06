<?php

declare(strict_types=1);

namespace Siberfx\BiletAll\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Siberfx\BiletAll\BiletAllServiceProvider;
use Siberfx\Soap\Facades\Soap;
use Siberfx\Soap\SoapServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SoapServiceProvider::class, BiletAllServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('biletall.username', 'demo-user');
        $app['config']->set('biletall.password', 'p<ss&');
        $app['config']->set('cache.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Soap::preventStrayCalls();
    }

    /**
     * Fake an XmlIslet response the way \SoapClient decodes an <s:any> result.
     */
    protected function fakeResult(string $xml): object
    {
        return (object) ['XmlIsletResult' => (object) ['any' => $xml]];
    }
}
