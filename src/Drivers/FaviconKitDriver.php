<?php

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;
use Illuminate\Support\Facades\Http;

class FaviconKitDriver implements Fetcher
{
    private const BASE_URL = 'https://api.faviconkit.com/';

    public function fetch(string $url): FetchedFavicon
    {
        $faviconUrl = self::BASE_URL.$url;

        $response = Http::get($faviconUrl);

        if ($response->successful()) {
            return new FetchedFavicon($faviconUrl);
        }

        // TODO Handle if it was invalid.
    }
}
