<?php

namespace AshAllenDesign\FaviconFetcher\Contracts;

use AshAllenDesign\FaviconFetcher\Favicon;

interface Fetcher
{
    public function fetch(string $url): ?Favicon;

    public function fetchOr(string $url, mixed $default): mixed;
}
