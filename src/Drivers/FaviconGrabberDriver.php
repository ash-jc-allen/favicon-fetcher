<?php

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Concerns\HasDefaultFunctionality;
use AshAllenDesign\FaviconFetcher\Concerns\ValidatesUrls;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\FeatureNotSupportedException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\Favicon;
use Illuminate\Support\Facades\Http;

class FaviconGrabberDriver implements Fetcher
{
    use ValidatesUrls;
    use HasDefaultFunctionality;

    private const BASE_URL = 'https://favicongrabber.com/api/grab/';

    /**
     * Attempt to fetch the favicon for the given URL.
     *
     * @param  string  $url
     * @return Favicon|null
     *
     * @throws InvalidUrlException
     * @throws FaviconNotFoundException
     */
    public function fetch(string $url): ?Favicon
    {
        if (! $this->urlIsValid($url)) {
            throw new InvalidUrlException($url.' is not a valid URL');
        }

        if ($this->useCache && $favicon = $this->attemptToFetchFromCache($url)) {
            return $favicon;
        }

        $urlWithoutProtocol = str_replace(['https://', 'http://'], '', $url);

        $apiUrl = self::BASE_URL.$urlWithoutProtocol;

        $response = Http::get($apiUrl);

        if (! $response->successful() || count($response->json('icons')) === 0) {
            return $this->notFound($url);
        }

        $faviconUrl = $response->json('icons')[0]['src'];

        return new Favicon(url: $url, faviconUrl: $faviconUrl, fromDriver: $this);
    }

    public function fetchAll(string $url): FaviconCollection
    {
        throw new FeatureNotSupportedException('The FaviconGrabber driver does not support fetching all favicons.');
    }
}
