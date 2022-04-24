<?php

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Concerns\HasDefaultFunctionality;
use AshAllenDesign\FaviconFetcher\Concerns\ValidatesUrls;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;
use Illuminate\Support\Facades\Http;

class FaviconKitDriver implements Fetcher
{
    use ValidatesUrls;
    use HasDefaultFunctionality;

    private const BASE_URL = 'https://api.faviconkit.com/';

    /**
     * @param string $url
     * @return FetchedFavicon|null
     * @throws InvalidUrlException
     */
    public function fetch(string $url): ?FetchedFavicon
    {
        if (! $this->urlIsValid($url)) {
            throw new InvalidUrlException($url.' is not a valid URL');
        }

        $faviconUrl = self::BASE_URL.$url;

        $response = Http::get($faviconUrl);

        return $response->successful() ? new FetchedFavicon($faviconUrl) : null;
    }
}
