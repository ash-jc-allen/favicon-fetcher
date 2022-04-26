<?php

namespace AshAllenDesign\FaviconFetcher;

use Illuminate\Support\ServiceProvider;

class FaviconFetcherProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/favicon-fetcher.php', 'favicon-fetcher');

        $this->app->bind('favicon-fetcher', fn () => new FetcherManager());
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/favicon-fetcher.php' => config_path('favicon-fetcher.php'),
        ], 'favicon-fetcher-config');
    }
}
