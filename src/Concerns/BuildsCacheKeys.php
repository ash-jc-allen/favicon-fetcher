<?php

namespace AshAllenDesign\FaviconFetcher\Concerns;

trait BuildsCacheKeys
{
    protected function buildCacheKey(string $url): string
    {
        return config('favicon-fetcher.cache.prefix').'.'.$url;
    }
}
