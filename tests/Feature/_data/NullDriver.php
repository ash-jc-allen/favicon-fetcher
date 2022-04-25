<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\_data;

use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;

class NullDriver implements Fetcher
{
    public static bool $flag = false;

    public function fetch(string $url): ?FetchedFavicon
    {
        static::$flag = true;

        return null;
    }

    public function fetchOr(string $url, mixed $default): mixed
    {
        return 'default';
    }
}
