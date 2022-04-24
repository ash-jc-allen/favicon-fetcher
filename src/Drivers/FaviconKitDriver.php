<?php

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Concerns\ValidatesUrls;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;
use Illuminate\Support\Facades\Http;

class FaviconKitDriver implements Fetcher
{
    use ValidatesUrls;

    private const BASE_URL = 'https://api.faviconkit.com/';

    /**
     * @param string $url
     * @return FetchedFavicon
     * @throws InvalidUrlException
     */
    public function fetch(string $url): FetchedFavicon
    {
        if (! $this->urlIsValid($url)) {
            throw new InvalidUrlException($url.' is not a valid URL');
        }

        $faviconUrl = self::BASE_URL.$url;

        $response = Http::get($faviconUrl);

        if ($response->successful()) {
            return new FetchedFavicon($faviconUrl);
        }

        // TODO Handle if it was invalid.
    }
}
