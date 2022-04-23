<?php

namespace AshAllenDesign\FaviconFetcher;

use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Drivers\FaviconKitDriver;
use AshAllenDesign\FaviconFetcher\Drivers\GoogleSharedStuffDriver;
use AshAllenDesign\FaviconFetcher\Drivers\HttpDriver;

class FetcherFactory
{
    public static function driver(string $driver): Fetcher
    {
        return match ($driver) {
            'http' => new HttpDriver(),
            'google-shared-stuff' => new GoogleSharedStuffDriver(),
            'favicon-kit' => new FaviconKitDriver(),
            default => throw new \Exception('Invalid driver'),
        };
    }
}
