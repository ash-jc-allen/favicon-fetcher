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

        // TODO Register favicon facade here
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
