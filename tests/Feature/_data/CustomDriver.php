<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\_data;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Favicon;

class CustomDriver implements Fetcher
{
    public function fetch(string $url): ?Favicon
    {
        return new Favicon(
            url: 'url-from-default',
            faviconUrl: 'favicon-from-default',
            fromDriver: $this,
        );
    }

    public function fetchOr(string $url, mixed $default): mixed
    {
        return 'default';
    }

    public function fetchAll(string $url): FaviconCollection
    {
        // TODO: Implement fetchAll() method.
    }
}
