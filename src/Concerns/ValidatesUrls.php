<?php

namespace AshAllenDesign\FaviconFetcher\Concerns;

trait ValidatesUrls
{
    protected function urlIsValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
}
