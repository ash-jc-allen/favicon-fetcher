<?php

namespace AshAllenDesign\FaviconFetcher\Concerns;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconFetcherException;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\FeatureNotSupportedException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\Facades\Favicon;
use AshAllenDesign\FaviconFetcher\Favicon as FetchedFavicon;
use Illuminate\Support\Facades\Cache;

trait HasDefaultFunctionality
{
    use BuildsCacheKeys;

    /**
     * An array of the drivers that should be as fallbacks if the current
     * driver fails to retrieve a favicon for the given URL.
     *
     * @var array
     */
    protected array $fallbacks = [];

    /**
     * Whether to throw an exception if the favicon cannot be found.
     *
     * @var bool
     */
    protected bool $throwOnNotFound = false;

    /**
     * Whether to attempt to retrieve the favicon URL from the cache.
     *
     * @var bool
     */
    protected bool $useCache = true;

    /**
     * Attempt to fetch the favicon for the given URL. If a favicon cannot
     * be found, return the default as a fallback.
     *
     * @param  string  $url
     * @param  mixed  $default
     * @return mixed
     *
     * @throws FaviconNotFoundException
     * @throws InvalidUrlException
     */
    public function fetchOr(string $url, mixed $default): mixed
    {
        if ($favicon = $this->fetch($url)) {
            return $favicon;
        }

        return $default instanceof \Closure ? $default($url) : $default;
    }

    /**
     * Attempt to fetch all the favicons for the given URL. If the favicons cannot
     * be found, return the default as a fallback.
     *
     * @param  string  $url
     * @param  mixed  $default
     * @return mixed
     *
     * @throws FaviconNotFoundException
     * @throws InvalidUrlException
     * @throws FeatureNotSupportedException
     */
    public function fetchAllOr(string $url, mixed $default): mixed
    {
        $favicons = $this->fetchAll($url);

        if ($favicons->isNotEmpty()) {
            return $favicons;
        }

        return $default instanceof \Closure ? $default($url) : $default;
    }

    /**
     * Specify whether to throw an exception if the favicon cannot be found.
     *
     * @param  bool  $throw
     * @return $this
     */
    public function throw(bool $throw = true): self
    {
        $this->throwOnNotFound = $throw;

        return $this;
    }

    /**
     * Specify which drivers should be used as fallbacks if the current
     * driver cannot find the favicon.
     *
     * @param  string  ...$fallbacks
     * @return $this
     */
    public function withFallback(string ...$fallbacks): self
    {
        $this->fallbacks = array_merge($this->fallbacks, $fallbacks);

        return $this;
    }

    /**
     * Specify whether to attempt to read the favicon from the cache.
     *
     * @param  bool  $useCache
     * @return $this
     */
    public function useCache(bool $useCache = true): self
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * Handle what happens if the favicon cannot be found using the current
     * driver. If any fallbacks are specified, attempt to find a favicon
     * using a different driver. If we have specified to throw an
     * exception, then do so. Otherwise, return null.
     *
     * @param  string  $url
     * @return FetchedFavicon|null
     *
     * @throws FaviconNotFoundException
     */
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

    /**
     * Loop through each fallback driver and attempt to retrieve a favicon.
     *
     * @param  string  $url
     * @return FetchedFavicon|null
     */
    protected function attemptFallbacks(string $url): ?FetchedFavicon
    {
        foreach ($this->fallbacks as $driver) {
            if ($favicon = Favicon::driver($driver)->fetch($url)) {
                return $favicon;
            }
        }

        return null;
    }

    /**
     * Return the cached favicon, if one exists, or return null.
     *
     * @param  string  $url
     * @return FetchedFavicon|null
     *
     * @throws FaviconFetcherException
     */
    protected function attemptToFetchFromCache(string $url): ?FetchedFavicon
    {
        $cachedFaviconData = Cache::get($this->buildCacheKey($url));

        if (! $cachedFaviconData) {
            return null;
        }

        // If the cached data is still stored in the older format used in
        // v1 of the package, then we convert it to the new format. In
        // v3 of the package, we will remove this check and enforce
        // an array to be stored.
        if (is_string($cachedFaviconData)) {
            $cachedFaviconData = [
                'favicon_url' => $cachedFaviconData,
                'icon_type' => FetchedFavicon::TYPE_ICON_UNKNOWN,
                'icon_size' => null,
            ];
        }

        return (new FetchedFavicon(
            url: $url,
            faviconUrl: $cachedFaviconData['favicon_url'],
            retrievedFromCache: true,
        ))
            ->setIconType($cachedFaviconData['icon_type'])
            ->setIconSize($cachedFaviconData['icon_size']);
    }

    /**
     * Return a collection of cached favicons if they exist, or return null.
     *
     * @param  string  $url
     * @return FaviconCollection|null
     *
     * @throws FaviconFetcherException
     */
    protected function attemptToFetchCollectionFromCache(string $url): ?FaviconCollection
    {
        $cachedFaviconsData = Cache::get($this->buildCacheKeyForCollection($url));

        if (! $cachedFaviconsData) {
            return null;
        }

        $favicons = new FaviconCollection();

        foreach ($cachedFaviconsData as $cachedFaviconData) {
            $favicons->push((new FetchedFavicon(
                url: $url,
                faviconUrl: $cachedFaviconData['favicon_url'],
                retrievedFromCache: true,
            ))
                ->setIconType($cachedFaviconData['icon_type'])
                ->setIconSize($cachedFaviconData['icon_size']));
        }

        return $favicons;
    }
}
