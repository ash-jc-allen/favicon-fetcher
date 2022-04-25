<?php

namespace AshAllenDesign\FaviconFetcher\Concerns;

trait BuildsCacheKeys
{
    protected function buildCacheKey(string $url): string
    {
        $url = str_replace(['http://', 'https://'], '', $url);

        return config('favicon-fetcher.cache.prefix').'.'.$url;
    }
}
