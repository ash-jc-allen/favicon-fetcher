<?php

namespace AshAllenDesign\FaviconFetcher\Contracts;

use AshAllenDesign\FaviconFetcher\FetchedFavicon;

interface Fetcher
{
    public function fetch(string $url): ?FetchedFavicon;

    public function fetchOr(string $url, mixed $default): mixed;

    public function fetchOrThrow(string $url): FetchedFavicon;
}
