<?php

namespace AshAllenDesign\FaviconFetcher;

use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Drivers\FaviconKitDriver;
use AshAllenDesign\FaviconFetcher\Drivers\GoogleSharedStuffDriver;
use AshAllenDesign\FaviconFetcher\Drivers\HttpDriver;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconFetcherException;

class FetcherManager
{
    protected static array $customDrivers = [];

    public static function driver(string $driver = null): Fetcher
    {
        $driver ??= config('favicon-fetcher.default');

        return match ($driver) {
            'http' => new HttpDriver(),
            'google-shared-stuff' => new GoogleSharedStuffDriver(),
            'favicon-kit' => new FaviconKitDriver(),
            default => static::attemptToCreateCustomDriver($driver),
        };
    }

    public static function extend(string $name, Fetcher $fetcher): void
    {
        self::$customDrivers[$name] = $fetcher;
    }

    protected static function attemptToCreateCustomDriver($driver): Fetcher
    {
        return static::$customDrivers[$driver]
            ?? throw new FaviconFetcherException($driver.' is not a valid driver.');
    }

    public function __call($method, $parameters)
    {
        return static::driver()->$method(...$parameters);
    }
}
