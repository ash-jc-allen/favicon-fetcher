<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\_data;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Favicon;

class NullDriver implements Fetcher
{
    public static bool $flag = false;

    public function fetch(string $url): ?Favicon
    {
        static::$flag = true;

        return null;
    }

    public function fetchOr(string $url, mixed $default): mixed
    {
        return 'default';
    }

    public function fetchAll(string $url): FaviconCollection
    {
        // Implement this method if needed for testing.
    }

    public function fetchAllOr(string $url, mixed $default): mixed
    {
        // Implement this method if needed for testing.
    }
}
