<?php

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;
use Illuminate\Support\Facades\Http;

class GoogleSharedStuffDriver implements Fetcher
{
    private const BASE_URL = 'https://www.google.com/s2/favicons?domain=';

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
