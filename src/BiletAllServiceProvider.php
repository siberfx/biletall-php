<?php

declare(strict_types=1);

namespace Siberfx\BiletAll;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Siberfx\BiletAll\Services\BiletAllClient;
use Siberfx\Soap\SoapWrapper;

class BiletAllServiceProvider extends ServiceProvider
{
    /**
     * Route file inside the app that, when present, replaces the package routes.
     */
    public const string ROUTE_OVERRIDE_PATH = 'routes/siberfx/biletall.php';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/biletall.php', 'biletall');

        $this->app->singleton(BiletAllClient::class, static fn (Application $app): BiletAllClient => new BiletAllClient(
            $app->make(SoapWrapper::class),
            $app->make('config')->get('biletall', []),
        ));
    }

    public function boot(): void
    {
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/biletall.php' => $this->app->configPath('biletall.php'),
            ], ['biletall-config', 'config']);

            $this->publishes([
                __DIR__.'/routes/biletall.php' => $this->app->basePath(self::ROUTE_OVERRIDE_PATH),
            ], 'biletall-routes');
        }
    }

    protected function registerRoutes(): void
    {
        if (! config('biletall.routes.enabled', true) || $this->app->routesAreCached()) {
            return;
        }

        $override = $this->app->basePath(self::ROUTE_OVERRIDE_PATH);

        $this->app->make('router')
            ->prefix((string) config('biletall.routes.prefix', 'bus'))
            ->middleware(config('biletall.routes.middleware', []))
            ->group(file_exists($override) ? $override : __DIR__.'/routes/biletall.php');
    }
}
