<?php

namespace AshAllenDesign\FaviconFetcher;

use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FetchedFavicon
{
    protected string $url;

    protected string $faviconUrl;

    protected ?Fetcher $driver = null;

    protected bool $retrievedFromCache = false;

    public function __construct(string $url, string $faviconUrl, Fetcher $fromDriver = null, bool $retrievedFromCache = false)
    {
        $this->url = $url;
        $this->faviconUrl = $faviconUrl;
        $this->driver = $fromDriver;
        $this->retrievedFromCache = $retrievedFromCache;
    }

    public static function makeFromCache(string $url, string $faviconUrl): self
    {
        return new self(url: $url, faviconUrl: $faviconUrl, retrievedFromCache: true);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getFaviconUrl(): string
    {
        return $this->faviconUrl;
    }

    public function content(): string
    {
        return Http::get($this->faviconUrl)->body();
    }

    public function cache(CarbonInterface $ttl): self
    {
        // If the favicon was retrieved from the cache, we don't want to try and cache it again.
        if (!$this->retrievedFromCache) {
            // TODO Move prefix to config.
            $cacheKey = 'favicon-fetcher.' . $this->url;

            Cache::put($cacheKey, $this->getFaviconUrl(), $ttl);
        }

        return $this;
    }
}
