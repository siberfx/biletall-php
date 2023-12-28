<?php

namespace Siberfx\BiletAll;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class BiletAllServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Where the route file lives, both inside the package and in the app (if overwritten).
     *
     * @var string
     */
    public $routeFilePath = '/routes/siberfx/biletall.php';

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupRoutes($this->app->router);

        $this->mergeConfigFrom(
            __DIR__ . '/config/biletall.php', 'biletall'
        );

        // publish config file
        $this->publishes([__DIR__.'/config' => config_path()], 'config');

    }


    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {

    }


    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function setupRoutes(Router $router)
    {
        // by default, use the routes file provided in vendor
        $routeFilePathInUse = __DIR__.$this->routeFilePath;

        // but if there's a file with the same name in routes/backpack, use that one
        if (file_exists(base_path().$this->routeFilePath)) {
            $routeFilePathInUse = base_path().$this->routeFilePath;
        }

        $this->loadRoutesFrom($routeFilePathInUse);
    }
}
