<?php

namespace AshAllenDesign\FaviconFetcher\Facades;

use Illuminate\Support\Facades\Facade;
use RuntimeException;

class Favicon extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'favicon-fetcher';
    }
}
