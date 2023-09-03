<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Concerns;

trait ValidatesUrls
{
    /**
     * Validate that the given parameter is a valid URL.
     *
     * @param  string  $url
     * @return bool
     */
    protected function urlIsValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
