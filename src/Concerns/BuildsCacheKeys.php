<?php

namespace AshAllenDesign\FaviconFetcher\Concerns;

trait BuildsCacheKeys
{
    /**
     * Build the key used for caching the favicon's URL.
     *
     * @param  string  $url
     * @return string
     */
    protected function buildCacheKey(string $url): string
    {
        $url = str_replace(['http://', 'https://'], '', $url);

        return config('favicon-fetcher.cache.prefix').'.'.$url;
    }
}
