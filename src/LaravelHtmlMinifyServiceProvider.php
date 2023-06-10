<?php

namespace C4N\LaravelHtmlMinify;

use Illuminate\Support\ServiceProvider;

class LaravelHtmlMinifyServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    /**
     * Bootstrap the application services.
     */

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/config/laravel-html-minify.php' => config_path('laravel-html-minify.php')
                ],
                'LaravelHtmlMinify'
            );
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/config/laravel-html-minify.php', 'LaravelHtmlMinify');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-html-minify', function () {
            return new \C4N\LaravelHtmlMinify\Facades\LaravelHtmlMinify;
        });

        $this->app['router']->middleware('LaravelHtmlMinify', 'C4N\LaravelHtmlMinify\Middlewares\LaravelHtmlMinify');

        $this->app['router']->aliasMiddleware('LaravelHtmlMinify', \C4N\LaravelHtmlMinify\Middlewares\LaravelHtmlMinify::class);
        $this->app['router']->pushMiddlewareToGroup('web', \C4N\LaravelHtmlMinify\Middlewares\LaravelHtmlMinify::class);
    }
}
