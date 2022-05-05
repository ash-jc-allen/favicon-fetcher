<?php

namespace AshAllenDesign\FaviconFetcher;

use AshAllenDesign\FaviconFetcher\Concerns\BuildsCacheKeys;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Favicon
{
    use BuildsCacheKeys;

    /**
     * The URL of the website that the favicon belongs to.
     *
     * @var string
     */
    protected string $url;

    /**
     * The URL of the favicon.
     *
     * @var string
     */
    protected string $faviconUrl;

    /**
     * The driver that was used to fetch the favicon. If the favicon was
     * retrieved from the cache, this will be null.
     *
     * @var Fetcher|null
     */
    protected ?Fetcher $driver = null;

    /**
     * Whether the favicon's URL was retrieved from the cache.
     *
     * @var bool
     */
    protected bool $retrievedFromCache = false;

    public function __construct(string $url, string $faviconUrl, Fetcher $fromDriver = null, bool $retrievedFromCache = false)
    {
        $this->url = $url;
        $this->faviconUrl = $faviconUrl;
        $this->driver = $fromDriver;
        $this->retrievedFromCache = $retrievedFromCache;
    }

    /**
     * Create a new Favicon object using data retrieved from the cache.
     *
     * @param  string  $url
     * @param  string  $faviconUrl
     * @return self
     */
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

    /**
     * Get the contents of the favicon file.
     *
     * @return string
     */
    public function content(): string
    {
        return Http::withOptions(['verify' => false])->get($this->faviconUrl)->body();
    }

    /**
     * Cache the favicon URL. If the favicon is already cached, "force"
     * must be passed as "true" to re-cache the URL.
     *
     * @param  CarbonInterface  $ttl
     * @param  bool  $force
     * @return $this
     */
    public function cache(CarbonInterface $ttl, bool $force = false): self
    {
        if ($force || ! $this->retrievedFromCache) {
            Cache::put($this->buildCacheKey($this->url), $this->getFaviconUrl(), $ttl);
        }

        return $this;
    }

    /**
     * Store the favicon in storage using an automatically generate filename.
     *
     * @param  string  $directory
     * @param  string|null  $disk
     * @return string
     */
    public function store(string $directory, string $disk = null): string
    {
        return $this->storeAs($directory, Str::uuid()->toString(), $disk);
    }

    /**
     * Store the favicon in storage.
     *
     * @param  string  $directory
     * @param  string  $filename
     * @param  string|null  $disk
     * @return string
     */
    public function storeAs(string $directory, string $filename, string $disk = null): string
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
