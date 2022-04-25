<?php

namespace AshAllenDesign\FaviconFetcher\Facades;

use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * @method static Fetcher driver(string $driver = null)
 * @method static void extend(string $name, Fetcher $fetcher)
 * @method static Fetcher throw(bool $throw = true)
 * @method static Fetcher withFallback(string ...$fallbacks)
 * @method static Fetcher useCache(bool $useCache = true)
 * @method static Favicon|null fetch(string $url)
 * @method static mixed fetchOr(string $url, mixed $default)
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
