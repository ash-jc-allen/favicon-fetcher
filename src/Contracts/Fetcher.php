<?php

namespace AshAllenDesign\FaviconFetcher\Contracts;

use AshAllenDesign\FaviconFetcher\FetchedFavicon;

interface Fetcher
{
    public function fetch(string $url): FetchedFavicon;
}
