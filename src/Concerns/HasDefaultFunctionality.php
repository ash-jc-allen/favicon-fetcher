<?php

namespace AshAllenDesign\FaviconFetcher\Concerns;

use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidArgumentException;
use AshAllenDesign\FaviconFetcher\Facades\Favicon;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;

trait HasDefaultFunctionality
{
    public function fetchOr(string $url, mixed $default): mixed
    {
        if ($favicon = $this->fetch($url)) {
            return $favicon;
        }

        return $default instanceof \Closure ? $default($url) : $default;
    }

    public function fetchOrFallback(string $url, array $drivers): ?FetchedFavicon
    {
        return $this->fetchOr($url, function (string $url) use ($drivers): ?FetchedFavicon {
            foreach ($drivers as $driver) {
                if ($favicon = Favicon::driver($driver)->fetch($url)) {
                    return $favicon;
                }
            }

            return null;
        });
    }

    public function fetchOrThrow(string $url): FetchedFavicon
    {
        return $this->fetchOr($url, function (string $url): void {
            throw new FaviconNotFoundException('A favicon cannot be found for '.$url);
        });
    }
}
