<?php

namespace AshAllenDesign\FaviconFetcher\Concerns;

trait BuildsCacheKeys
{
    /**
     * Build the key used for caching the favicon.
     *
     * @param  string  $url
     * @return string
     */
    public function buildCacheKey(string $url): string
    {
        $url = str_replace(['http://', 'https://'], '', $url);

        return config('favicon-fetcher.cache.prefix').'.'.$url;
    }

    /**
     * Build the key used for caching the favicon collection.
     *
     * @param  string  $url
     * @return string
     */
    public function buildCacheKeyForCollection(string $url): string
    {
        return $this->buildCacheKey($url).'.collection';
    }
}
