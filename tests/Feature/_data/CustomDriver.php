<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\_data;

use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;

class CustomDriver implements Fetcher
{
    public function fetch(string $url): ?FetchedFavicon
    {
        return new FetchedFavicon('url-from-default', 'favicon-from-default');
    }

    public function fetchOr(string $url, mixed $default): mixed
    {
        return 'default';
    }
}
