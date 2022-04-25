<?php

namespace AshAllenDesign\FaviconFetcher\Concerns;

use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Facades\Favicon;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;
use Illuminate\Support\Facades\Cache;

trait HasDefaultFunctionality
{
    protected array $fallbacks = [];

    protected bool $throwOnNotFound = false;

    protected bool $useCache = true;

    public function fetchOr(string $url, mixed $default): mixed
    {
        if ($favicon = $this->fetch($url)) {
            return $favicon;
        }

        return $default instanceof \Closure ? $default($url) : $default;
    }

    public function throw(bool $throw = true): self
    {
        $this->throwOnNotFound = $throw;

        return $this;
    }

    protected function notFound(string $url)
    {
        if ($favicon = $this->attemptFallbacks($url)) {
            return $favicon;
        }

        if ($this->throwOnNotFound) {
            throw new FaviconNotFoundException('A favicon cannot be found for '.$url);
        }

        return null;
    }

    protected function attemptFallbacks(string $url): ?FetchedFavicon
    {
        foreach ($this->fallbacks as $driver) {
            if ($favicon = Favicon::driver($driver)->fetch($url)) {
                return $favicon;
            }
        }

        return null;
    }

    public function withFallback(string ...$fallbacks): self
    {
        $this->fallbacks = array_merge($this->fallbacks, $fallbacks);

        return $this;
    }

    public function useCache(bool $useCache = true): self
    {
        $this->useCache = $useCache;

        return $this;
    }

    protected function attemptToFetchFromCache(string $url): ?FetchedFavicon
    {
        // TODO Lift the cache key into a central place.
        $cachedFaviconUrl = Cache::get(config('favicon-fetcher.cache.prefix').'.'.$url);

        return $cachedFaviconUrl ? FetchedFavicon::makeFromCache($url, $cachedFaviconUrl) : null;
    }
}
