<?php

namespace AshAllenDesign\FaviconFetcher;

use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// TODO Maybe rename me to just favicon?
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

    public function retrievedFromCache(): bool
    {
        return $this->retrievedFromCache;
    }

    public function content(): string
    {
        return Http::get($this->faviconUrl)->body();
    }

    public function cache(CarbonInterface $ttl, bool $force = false): self
    {
        // If the favicon was retrieved from the cache, we don't want to try and cache it again.
        if ($force || ! $this->retrievedFromCache) {
            // TODO Move prefix to config.
            $cacheKey = 'favicon-fetcher.'.$this->url;

            Cache::put($cacheKey, $this->getFaviconUrl(), $ttl);
        }

        return $this;
    }

    public function store(string $directory, string $disk = null): string
    {
        return $this->storeAs($directory, Str::uuid()->toString(), $disk);
    }

    public function storeAs(string $directory, string $filename, string $disk = null)
    {
        $path = $this->buildStoragePath($directory, $filename);

        Storage::disk($disk)->put($path, $this->content());

        return $path;
    }

    protected function buildStoragePath(string $directory, string $filename): string
    {
        return Str::of($directory)
            ->append('/')
            ->append($filename)
            ->append('.')
            ->append(File::extension($this->faviconUrl));
    }
}
