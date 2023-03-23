<?php

namespace AshAllenDesign\FaviconFetcher\Facades;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Favicon as FetchedFavicon;
use AshAllenDesign\FaviconFetcher\FetcherManager;
use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * @method static Fetcher driver(string $driver = null)
 * @method static void extend(string $name, Fetcher $fetcher)
 * @method static Fetcher throw(bool $throw = true)
 * @method static Fetcher withFallback(string ...$fallbacks)
 * @method static Fetcher useCache(bool $useCache = true)
 * @method static FetchedFavicon|null fetch(string $url)
 * @method static mixed fetchOr(string $url, mixed $default)
 * @method static FaviconCollection fetchAll(string $url)
 * @method static mixed fetchAllOr(string $url, mixed $default)
 *
 * @see FetcherManager
 */
class Favicon extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'favicon-fetcher';
    }
}
