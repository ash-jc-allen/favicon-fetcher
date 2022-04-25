<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature;

use AshAllenDesign\FaviconFetcher\FaviconFetcherProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Load package service provider.
     *
     * @param $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [FaviconFetcherProvider::class];
    }
}
